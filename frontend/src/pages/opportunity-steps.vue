<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import { useOpportunityInsight } from '@/composables/useOpportunityInsight'
import {
  closeOpportunityStep,
  fetchOpportunity,
  fetchOpportunityStages,
  getCompany,
  getUser,
} from '@/utils/api'
import { formatJalaliFa, toFaDigits } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotify()
const id = computed(() => Number(route.params.id))
const fromSource = computed(() => String(route.query.from || ''))
const currentUser = computed(() => getUser<{ id?: number; name?: string }>())
const { insight: serverInsight, loading: insightLoading, loadInsight } = useOpportunityInsight(() => id.value)

const item = ref<any | null>(null)
const stages = ref<any[]>([])
const loading = ref(false)
const closeOpen = ref(false)
const closing = ref(false)
const formRef = ref()
const dirty = ref(false)
const selectedStepId = ref<number | null>(null)

const closeForm = ref({
  result: '',
  stage_id: null as number | null,
  status: 'active',
  next_step_title: '',
  next_step_due_on: null as string | null,
})

watch(closeForm, () => { dirty.value = true }, { deep: true })

const statusLabel: Record<string, string> = {
  active: 'فعال', nurturing: 'در حال پرورش', paused: 'متوقف موقت', won: 'بسته‌شده-موفق', lost: 'بسته‌شده-ناموفق',
}
const typeLabel: Record<string, string> = {
  call: 'تماس', meeting: 'جلسه', message: 'ایمیل / پیام', visit: 'بازدید', proposal: 'ارسال پیشنهاد', other: 'سایر', note: 'یادداشت', referral: 'ارجاع',
}

const currentStep = computed(() => {
  const d = item.value
  if (!d) return null
  if (d.current_step) return d.current_step
  const open = (d.steps || []).find((s: any) => s.is_current && s.status === 'open')
  if (open) return open
  return d.next_step ? { title: d.next_step, due_on: d.next_step_due_on, id: null, assignee: d.assignee } : null
})

const allSteps = computed(() => {
  const steps = [...(item.value?.steps || [])]
  return steps.sort((a: any, b: any) => {
    const ao = a.status === 'open' || a.is_current ? 0 : 1
    const bo = b.status === 'open' || b.is_current ? 0 : 1
    if (ao !== bo) return ao - bo
    return Number(b.id || 0) - Number(a.id || 0)
  })
})

const viewingStep = computed(() => {
  if (selectedStepId.value != null) {
    const found = allSteps.value.find((s: any) => s.id === selectedStepId.value)
    if (found) return found
  }
  return currentStep.value
})

const stepActivities = computed(() => {
  const d = item.value
  if (!d?.activities?.length) return [] as any[]
  const stepId = viewingStep.value?.id
  const list = stepId ? d.activities.filter((a: any) => a.step_id === stepId) : d.activities.filter((a: any) => !a.step_id)
  return [...list].sort((a: any, b: any) => {
    const ca = a.created_at ? new Date(a.created_at).getTime() : 0
    const cb = b.created_at ? new Date(b.created_at).getTime() : 0
    if (ca !== cb) return cb - ca
    return Number(b.id || 0) - Number(a.id || 0)
  })
})

const productList = computed(() => {
  if (!item.value) return [] as string[]
  if (Array.isArray(item.value.products) && item.value.products.length) return item.value.products
  if (item.value.product_name) return [item.value.product_name]
  return []
})

function activitiesOfStep(stepId: number | null | undefined) {
  if (!item.value?.activities?.length || !stepId) return [] as any[]
  return item.value.activities.filter((a: any) => a.step_id === stepId)
}
function daysUntil(due: string | null | undefined): number | null {
  if (!due) return null
  const d = new Date(due)
  if (Number.isNaN(d.getTime())) return null
  const today = new Date(); today.setHours(0,0,0,0); d.setHours(0,0,0,0)
  return Math.round((d.getTime() - today.getTime()) / 86400000)
}
function deadlineMeta(due: string | null | undefined) {
  const days = daysUntil(due)
  if (days == null) return { tone: 'neutral', label: 'بدون مهلت', detail: 'مهلت مشخص نشده', days: null as number | null }
  if (days < 0) return { tone: 'danger', label: 'گذشته از مهلت', detail: `${toFaDigits(Math.abs(days))} روز تأخیر`, days }
  if (days === 0) return { tone: 'warn', label: 'امروز', detail: 'مهلت امروز است', days }
  if (days <= 2) return { tone: 'warn', label: 'نزدیک', detail: `${toFaDigits(days)} روز مانده`, days }
  if (days <= 7) return { tone: 'info', label: 'در بازه', detail: `${toFaDigits(days)} روز مانده`, days }
  return { tone: 'ok', label: 'آرام', detail: `${toFaDigits(days)} روز مانده`, days }
}
const currentDeadline = computed(() => deadlineMeta(viewingStep.value?.due_on || currentStep.value?.due_on))
function stepAssigneeName(step: any) {
  return step?.assignee?.name || step?.owner?.name || item.value?.assignee?.name || item.value?.owner?.name || null
}
function stepAssigneeId(step: any) {
  return step?.assignee?.id || step?.owner?.id || item.value?.assignee?.id || item.value?.owner?.id || null
}
const isMyStep = computed(() => {
  const uid = currentUser.value?.id
  const aid = stepAssigneeId(viewingStep.value || currentStep.value)
  return uid != null && aid != null && Number(uid) === Number(aid)
})
const assigneeLabel = computed(() => isMyStep.value ? 'خودت' : (stepAssigneeName(viewingStep.value || currentStep.value) || 'نامشخص'))

const smartInsight = computed(() => {
  const d = item.value
  if (!d) return null

  const steps = d.steps || []
  const activities = d.activities || []
  const openSteps = steps.filter((s: any) => s.status === 'open' || s.is_current)
  const closed = steps.filter((s: any) => s.status === 'closed')
  const overdue = openSteps.filter((s: any) => {
    const days = daysUntil(s.due_on)
    return days != null && days < 0
  })
  const recentActs = [...activities]
    .sort((a: any, b: any) => Number(b.id || 0) - Number(a.id || 0))
    .slice(0, 2)

  let alert: string | null = null
  let scoreTone: 'ok' | 'warn' | 'danger' = 'ok'
  let scoreLabel = 'پایدار'

  if (overdue.length) {
    alert = `${toFaDigits(overdue.length)} گام از مهلت گذشته — امروز ببندید یا مهلت را تمدید کنید.`
    scoreTone = 'danger'
    scoreLabel = 'نیازمند توجه'
  }
  else if (currentDeadline.value.tone === 'warn' || currentDeadline.value.tone === 'danger') {
    alert = `مهلت گام جاری: ${currentDeadline.value.detail}`
    scoreTone = currentDeadline.value.tone === 'danger' ? 'danger' : 'warn'
    scoreLabel = scoreTone === 'danger' ? 'نیازمند توجه' : 'حساس'
  }
  else if (!activities.length) {
    alert = 'هنوز فعالیتی ثبت نشده؛ مسیر فرصت هنوز روشن نیست.'
    scoreTone = 'warn'
    scoreLabel = 'حساس'
  }

  let suggestion = 'یک پیگیری کوتاه ثبت کنید و نتیجه را بنویسید.'
  if (overdue.length)
    suggestion = 'گام معوق را با نتیجه واقعی ببندید یا مهلت جدید بگذارید.'
  else if (!activities.length && currentStep.value)
    suggestion = `برای «${currentStep.value.title}» یک تماس یا جلسه ثبت کنید.`
  else if (currentDeadline.value.tone === 'warn')
    suggestion = 'امروز با مخاطب تماس بگیرید و وضعیت را مشخص کنید.'
  else if (closed.length && openSteps.length)
    suggestion = 'گام فعلی قابل ادامه است؛ گام بعدی را طراحی کنید.'
  else if (d.status === 'nurturing')
    suggestion = 'یک نمونه یا محتوای مفید برای مشتری بفرستید.'

  if (serverInsight.value?.suggestion)
    suggestion = serverInsight.value.suggestion
  if (serverInsight.value?.score_tone === 'danger' || serverInsight.value?.score_tone === 'warn') {
    scoreTone = serverInsight.value.score_tone
    scoreLabel = serverInsight.value.score_label || scoreLabel
  }

  const metrics = [
    { label: 'وضعیت', value: statusLabel[d.status] || d.status },
    { label: 'مرحله', value: d.stage?.name || '—' },
    { label: 'گام باز', value: toFaDigits(openSteps.length) },
    { label: 'فعالیت', value: toFaDigits(activities.length) },
  ]

  const focus = currentStep.value
    ? `گام جاری: ${currentStep.value.title}`
    : 'گام بازی تعریف نشده'

  const recent = recentActs.length
    ? recentActs.map((a: any) => typeLabel[a.type] || a.subject || a.type).join('، ')
    : null

  return { scoreLabel, scoreTone, alert, focus, metrics, recent, suggestion, source: 'local' as const }
})

async function load() {
  if (!getCompany()?.id) { notifyError('شرکت جاری مشخص نشده است.'); return }
  loading.value = true
  try {
    const [res, stageRes] = await Promise.all([fetchOpportunity(id.value), fetchOpportunityStages()])
    item.value = res.data
    stages.value = (stageRes.data || []).map((s: any) => ({ title: s.name, value: s.id }))
    closeForm.value.stage_id = res.data?.stage?.id ?? null
    closeForm.value.status = res.data?.status || 'active'
    dirty.value = false
    selectedStepId.value = res.data?.current_step?.id ?? null
    void loadInsight()
  } catch (e: any) {
    notifyError(e?.message || 'خطا در بارگذاری')
  } finally {
    loading.value = false
  }
}

function goDashboard() { router.push('/dashboard') }
function goList() { router.push('/opportunities') }
function goBack() {
  if (fromSource.value === 'list') { router.push('/opportunities'); return }
  if (fromSource.value === 'activity') { router.push('/opportunities/' + id.value + '/activities/new?from=steps'); return }
  if (fromSource.value === 'detail') { router.push('/opportunities/' + id.value); return }
  if (typeof window !== 'undefined' && window.history.length > 1) { router.back(); return }
  router.push('/opportunities')
}
function goActivityNew() { router.push(`/opportunities/${id.value}/activities/new?from=steps`) }
function selectStep(step: any) { selectedStepId.value = step?.id ?? null }
function openClose() {
  closeForm.value = { result: '', next_step_title: '', next_step_due_on: null, stage_id: item.value?.stage?.id ?? null, status: item.value?.status || 'active' }
  dirty.value = false
  closeOpen.value = true
}
async function submitClose() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid) return
  closing.value = true
  try {
    const res = await closeOpportunityStep(id.value, {
      result: closeForm.value.result,
      stage_id: closeForm.value.stage_id,
      status: closeForm.value.status,
      next_step_title: closeForm.value.next_step_title || null,
      next_step_due_on: closeForm.value.next_step_due_on,
    })
    item.value = res.data
    dirty.value = false
    closeOpen.value = false
    success('گام بسته شد')
    void loadInsight()
  } catch (e: any) {
    notifyError(e?.message || 'بستن گام ناموفق')
  } finally {
    closing.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="steps-workspace">
    <div v-if="loading" class="text-center py-10 text-medium-emphasis">در حال بارگذاری...</div>
    <template v-else-if="item">
      <div class="page-breadcrumb d-flex flex-wrap align-center gap-1 text-caption text-medium-emphasis mb-2">
        <button type="button" class="crumb-btn" @click="goDashboard">خانه</button>
        <VIcon icon="ri-arrow-left-s-line" size="14" />
        <button type="button" class="crumb-btn" @click="goList">فرصت‌ها</button>
        <VIcon icon="ri-arrow-left-s-line" size="14" />
        <span class="text-truncate">{{ item.organization_name }}</span>
      </div>
      <div class="page-head d-flex flex-wrap align-center gap-2 mb-4">
        <VBtn icon variant="text" color="secondary" size="small" density="comfortable" @click="goBack"><VIcon icon="ri-arrow-right-s-line" size="26" /></VBtn>
        <h1 class="page-title text-h6 font-weight-medium mb-0 text-wrap">مدیریت گام‌های «{{ item.organization_name }}»</h1>
      </div>

      <VRow dense>
        <VCol cols="12" lg="8">
          <div v-if="currentStep || viewingStep" class="hero-step mb-3" :class="`hero-step--${currentDeadline.tone}`">
            <div class="hero-step__glow" />
            <div class="hero-step__body">
              <div class="d-flex flex-wrap align-start justify-space-between gap-3">
                <div class="min-w-0 flex-grow-1">
                  <div class="d-flex flex-wrap align-center gap-2 mb-2">
                    <span class="hero-badge">گام {{ selectedStepId && selectedStepId !== currentStep?.id ? 'انتخاب‌شده' : 'جاری' }}</span>
                    <span class="deadline-pill" :class="`deadline-pill--${currentDeadline.tone}`">{{ currentDeadline.label }} · {{ currentDeadline.detail }}</span>
                  </div>
                  <h2 class="hero-title text-wrap mb-3">{{ viewingStep?.title || currentStep?.title }}</h2>
                  <div class="d-flex flex-wrap gap-3">
                    <div class="meta-chip"><VIcon icon="ri-calendar-event-line" size="18" /><span>مهلت {{ formatJalaliFa(viewingStep?.due_on || currentStep?.due_on) || '—' }}</span></div>
                    <div class="meta-chip" :class="{ 'meta-chip--me': isMyStep }"><VIcon icon="ri-user-star-line" size="18" /><span>مسئول: {{ assigneeLabel }}</span></div>
                  </div>
                </div>
                <div class="d-flex flex-column gap-2 action-stack">
                  <button type="button" class="gfx-btn gfx-btn--close" @click="openClose"><span class="gfx-btn__icon"><VIcon icon="ri-checkbox-circle-fill" size="22" /></span><span class="gfx-btn__text"><strong>بستن گام</strong><small>ثبت نتیجه و ادامه</small></span></button>
                  <button type="button" class="gfx-btn gfx-btn--delete" disabled><span class="gfx-btn__icon"><VIcon icon="ri-delete-bin-6-fill" size="22" /></span><span class="gfx-btn__text"><strong>حذف گام</strong><small>غیرفعال تا تأیید</small></span></button>
                </div>
              </div>
            </div>
          </div>

          <VCard class="mb-3 main-card"><VCardText class="pa-4">
            <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
              <div class="text-subtitle-2 font-weight-medium d-flex align-center gap-2"><span class="section-orb section-orb--primary" />فعالیت‌های این گام</div>
              <VBtn color="primary" size="small" variant="tonal" prepend-icon="ri-add-line" @click="goActivityNew">ثبت فعالیت</VBtn>
            </div>
            <div v-if="!stepActivities.length" class="empty-soft">هنوز فعالیتی برای این گام ثبت نشده.</div>
            <div v-for="a in stepActivities" :key="a.id" class="activity-item">
              <div class="activity-item__bar" />
              <div class="min-w-0 flex-grow-1">
                <div class="d-flex flex-wrap align-center gap-2 mb-1">
                  <span class="text-body-2 font-weight-medium">{{ typeLabel[a.type] || a.subject || a.type }}</span>
                  <span class="text-caption text-medium-emphasis">{{ formatJalaliFa(a.happened_at || a.created_at) }}</span>
                </div>
                <div class="text-body-2 text-medium-emphasis text-wrap">{{ a.body }}</div>
              </div>
            </div>
          </VCardText></VCard>

          <VCard class="mb-3 main-card"><VCardText class="pa-4">
            <div class="text-subtitle-2 font-weight-medium d-flex align-center gap-2 mb-3"><span class="section-orb section-orb--secondary" />فهرست گام‌ها</div>
            <div v-if="!allSteps.length" class="empty-soft">گامی ثبت نشده است.</div>
            <VTable v-else density="comfortable" class="steps-table">
              <thead><tr><th>عنوان گام</th><th>وضعیت</th><th>مهلت</th><th>فعالیت</th><th></th></tr></thead>
              <tbody>
                <tr v-for="s in allSteps" :key="s.id" class="steps-table__row" :class="{ 'steps-table__row--active': selectedStepId === s.id }" @click="selectStep(s)">
                  <td class="font-weight-medium">{{ s.title }}</td>
                  <td><VChip size="x-small" :color="s.status === 'open' || s.is_current ? 'primary' : 'secondary'" variant="tonal">{{ s.status === 'open' || s.is_current ? 'باز' : 'بسته' }}</VChip></td>
                  <td><span class="deadline-dot" :class="`deadline-dot--${deadlineMeta(s.due_on).tone}`" />{{ formatJalaliFa(s.due_on) || '—' }}</td>
                  <td>{{ toFaDigits(activitiesOfStep(s.id).length) }}</td>
                  <td class="text-end"><VIcon icon="ri-arrow-left-s-line" size="18" class="text-medium-emphasis" /></td>
                </tr>
              </tbody>
            </VTable>
          </VCardText></VCard>
        </VCol>

        <VCol cols="12" lg="4">
                    <VCard v-if="smartInsight" class="mb-3 insight-card">
            <VCardText class="pa-3">
              <div class="insight-head d-flex align-center justify-space-between gap-2 mb-3">
                <div class="text-body-2 d-flex align-center gap-1 text-medium-emphasis">
                  <VIcon icon="ri-pulse-line" size="18" color="primary" />
                  وضعیت فرصت
                </div>
                <VChip
                  size="small"
                  variant="flat"
                  :color="smartInsight.scoreTone === 'danger' ? 'error' : smartInsight.scoreTone === 'warn' ? 'warning' : 'success'"
                >
                  {{ smartInsight.scoreLabel }}
                </VChip>
              </div>

              <div
                v-if="smartInsight.alert"
                class="insight-alert mb-3"
                :class="`insight-alert--${smartInsight.scoreTone}`"
              >
                <VIcon
                  :icon="smartInsight.scoreTone === 'ok' ? 'ri-information-line' : 'ri-error-warning-line'"
                  size="18"
                  class="flex-shrink-0 mt-1"
                />
                <span>{{ smartInsight.alert }}</span>
              </div>

              <div class="insight-metrics mb-3">
                <div v-for="(m, i) in smartInsight.metrics" :key="i" class="insight-metric">
                  <span class="insight-metric__label">{{ m.label }}</span>
                  <span class="insight-metric__value">{{ m.value }}</span>
                </div>
              </div>

              <div class="insight-focus text-caption text-medium-emphasis mb-1">{{ smartInsight.focus }}</div>
              <div v-if="smartInsight.recent" class="text-caption text-disabled mb-3">
                اخیر: {{ smartInsight.recent }}
              </div>

              <div class="insight-action">
                <div class="insight-action__label">کار پیشنهادی</div>
                <div class="insight-action__text">{{ smartInsight.suggestion }}</div>
              </div>
            </VCardText>
          </VCard>

          <VCard class="mb-3 side-card side-card--compact"><VCardText class="pa-3">
            <div class="text-caption text-medium-emphasis mb-1">فرصت</div>
            <div class="text-body-1 font-weight-medium mb-2 text-wrap">{{ item.organization_name }}</div>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <VChip size="x-small" color="primary" variant="tonal">{{ toFaDigits(item.code) }}</VChip>
              <VChip size="x-small" color="success" variant="tonal">{{ statusLabel[item.status] || item.status }}</VChip>
              <VChip v-if="item.stage?.name" size="x-small" variant="tonal">{{ item.stage.name }}</VChip>
            </div>
            <div class="compact-grid">
              <div><span class="k">مخاطب</span><span class="v">{{ item.contact_name || '—' }}</span></div>
              <div><span class="k">محصول</span><span class="v">{{ productList[0] || '—' }}</span></div>
              <div><span class="k">مالک</span><span class="v">{{ item.owner?.name || '—' }}</span></div>
              <div><span class="k">ارجاع</span><span class="v">{{ item.assignee?.name || '—' }}</span></div>
            </div>
          </VCardText></VCard>
        </VCol>
      </VRow>
    </template>

    <FormDrawer v-model="closeOpen" title="بستن گام" subtitle="نتیجه و گام بعدی را مشخص کنید" :dirty="dirty" :width="480">
      <VForm ref="formRef" @submit.prevent="submitClose">
        <VTextarea v-model="closeForm.result" label="نتیجه این گام" rows="3" class="mb-3" :rules="[rules.required('نتیجه')]" />
        <VSelect v-model="closeForm.stage_id" :items="stages" label="مرحله قیف" class="mb-3" />
        <VSelect v-model="closeForm.status" :items="[{ title: 'فعال', value: 'active' }, { title: 'در حال پرورش', value: 'nurturing' }, { title: 'متوقف موقت', value: 'paused' }, { title: 'بسته‌شده-موفق', value: 'won' }, { title: 'بسته‌شده-ناموفق', value: 'lost' }]" label="وضعیت فرصت" class="mb-3" />
        <VTextField v-model="closeForm.next_step_title" label="گام بعدی (اختیاری)" class="mb-3" />
        <JalaliDateField v-model="closeForm.next_step_due_on" label="مهلت گام بعدی" />
      </VForm>
      <template #actions>
        <VBtn variant="text" color="secondary" @click="closeOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="closing" @click="submitClose">بستن گام</VBtn>
      </template>
    </FormDrawer>
  </div>
</template>

<style scoped>
.steps-workspace { font-family: 'Vazirmatn', Vazirmatn, Tahoma, sans-serif !important; }
.steps-workspace :deep(*) { font-family: 'Vazirmatn', Vazirmatn, Tahoma, sans-serif !important; }
.page-title { line-height: 1.4; }
.crumb-btn { background: none; border: 0; padding: 0; cursor: pointer; font: inherit; color: inherit; }
.crumb-btn:hover { color: rgb(var(--v-theme-primary)); }
.hero-step { position: relative; border-radius: 16px; overflow: hidden; border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); background: rgb(var(--v-theme-surface)); box-shadow: 0 10px 28px rgba(0,0,0,0.06); }
.hero-step::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 6px; background: #9e9e9e; }
.hero-step--danger::before { background: linear-gradient(180deg,#ff5252,#c62828); }
.hero-step--warn::before { background: linear-gradient(180deg,#ffb74d,#ef6c00); }
.hero-step--info::before { background: linear-gradient(180deg,#64b5f6,#1565c0); }
.hero-step--ok::before { background: linear-gradient(180deg,#81c784,#2e7d32); }
.hero-step--neutral::before { background: linear-gradient(180deg,#bdbdbd,#757575); }
.hero-step--danger { background: linear-gradient(135deg,rgba(244,67,54,.08),rgb(var(--v-theme-surface)) 55%); }
.hero-step--warn { background: linear-gradient(135deg,rgba(255,152,0,.1),rgb(var(--v-theme-surface)) 55%); }
.hero-step--info { background: linear-gradient(135deg,rgba(33,150,243,.08),rgb(var(--v-theme-surface)) 55%); }
.hero-step--ok { background: linear-gradient(135deg,rgba(76,175,80,.08),rgb(var(--v-theme-surface)) 55%); }
.hero-step__glow { position: absolute; inset-inline-end: -40px; inset-block-start: -40px; width: 160px; height: 160px; border-radius: 50%; background: radial-gradient(circle,rgba(var(--v-theme-primary),.18),transparent 70%); pointer-events: none; }
.hero-step__body { position: relative; padding: 1.1rem 1.25rem; }
.hero-badge { display: inline-flex; padding: .15rem .55rem; border-radius: 999px; font-size: .75rem; background: rgba(var(--v-theme-primary),.12); color: rgb(var(--v-theme-primary)); font-weight: 600; }
.hero-title { font-size: 1.2rem; font-weight: 700; line-height: 1.45; margin: 0; }
.deadline-pill { display: inline-flex; padding: .15rem .55rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
.deadline-pill--danger { background: rgba(244,67,54,.15); color: #c62828; }
.deadline-pill--warn { background: rgba(255,152,0,.18); color: #e65100; }
.deadline-pill--info { background: rgba(33,150,243,.15); color: #1565c0; }
.deadline-pill--ok { background: rgba(76,175,80,.15); color: #2e7d32; }
.deadline-pill--neutral { background: rgba(158,158,158,.15); color: #616161; }
.meta-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .65rem; border-radius: 10px; background: rgba(var(--v-theme-on-surface),.04); font-size: .875rem; }
.meta-chip--me { background: rgba(var(--v-theme-primary),.12); color: rgb(var(--v-theme-primary)); font-weight: 600; }
.gfx-btn { display: flex; align-items: center; gap: .65rem; min-inline-size: 11.5rem; padding: .55rem .75rem; border-radius: 12px; border: 0; cursor: pointer; text-align: start; color: #fff; box-shadow: 0 8px 18px rgba(0,0,0,.12), inset 0 1px 0 rgba(255,255,255,.25); }
.gfx-btn:disabled { opacity: .55; cursor: not-allowed; }
.gfx-btn--close { background: linear-gradient(145deg,#42a5f5,#1e88e5 45%,#1565c0); }
.gfx-btn--delete { background: linear-gradient(145deg,#ef9a9a,#e53935 45%,#b71c1c); }
.gfx-btn__icon { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.18); }
.gfx-btn__text { display: flex; flex-direction: column; line-height: 1.2; }
.gfx-btn__text strong { font-size: .9rem; }
.gfx-btn__text small { font-size: .7rem; opacity: .9; }
.main-card,.side-card { border: 1px solid rgba(var(--v-border-color),var(--v-border-opacity)); box-shadow: 0 6px 18px rgba(0,0,0,.04)!important; border-radius: 14px!important; }
.section-orb { width: 10px; height: 10px; border-radius: 50%; display: inline-block; box-shadow: 0 0 0 4px rgba(var(--v-theme-primary),.12); }
.section-orb--primary { background: rgb(var(--v-theme-primary)); }
.section-orb--secondary { background: #7e57c2; box-shadow: 0 0 0 4px rgba(126,87,194,.15); }
.activity-item { display: flex; gap: .75rem; padding: .85rem .75rem; border-radius: 12px; margin-block-end: .65rem; background: rgba(var(--v-theme-on-surface),.025); border: 1px solid rgba(var(--v-border-color),var(--v-border-opacity)); }
.activity-item__bar { width: 4px; border-radius: 4px; background: linear-gradient(180deg,rgb(var(--v-theme-primary)),rgba(var(--v-theme-primary),.3)); flex-shrink: 0; }
.empty-soft { padding: 1rem; border-radius: 12px; background: rgba(var(--v-theme-on-surface),.03); color: rgba(var(--v-theme-on-surface),var(--v-medium-emphasis-opacity)); font-size: .875rem; }
.steps-table__row { cursor: pointer; }
.steps-table__row:hover { background: rgba(var(--v-theme-primary),.05); }
.steps-table__row--active { background: rgba(var(--v-theme-primary),.08); }
.deadline-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-inline-end: .35rem; vertical-align: middle; }
.deadline-dot--danger { background: #e53935; }
.deadline-dot--warn { background: #fb8c00; }
.deadline-dot--info { background: #1e88e5; }
.deadline-dot--ok { background: #43a047; }
.deadline-dot--neutral { background: #9e9e9e; }



.compact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .45rem .75rem; font-size: .8rem; }
.compact-grid .k { display: block; color: rgba(var(--v-theme-on-surface),var(--v-medium-emphasis-opacity)); font-size: .72rem; }
.compact-grid .v { font-weight: 600; }
@media (max-width:600px) { .gfx-btn { min-inline-size: 100%; } .action-stack { width: 100%; } }
</style>
