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
import { activityBarColor, activityVisual, stageVisual } from '@/utils/opportunityVisuals'
import { formatJalaliFa, toFaDigits } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotify()
const id = computed(() => Number(route.params.id))
const fromSource = computed(() => String(route.query.from || ''))
const currentUser = computed(() => getUser<{
  id?: number
  name?: string
  roles?: string[]
  permissions?: string[]
}>())
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

const canManageStep = computed(() => {
  const u = currentUser.value
  if (!u) return false
  if ((u.roles || []).includes('admin')) return true
  return (u.permissions || []).includes('opportunity.step.manage')
})

const currentStep = computed(() => {
  const d = item.value
  if (!d) return null
  if (d.current_step) return d.current_step
  const open = (d.steps || []).find((s: any) => s.is_current && s.status === 'open')
  if (open) return open
  return d.next_step ? { title: d.next_step, due_on: d.next_step_due_on, id: null, assignee: d.assignee, status: 'open' } : null
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

const isViewingOpen = computed(() => {
  const s = viewingStep.value
  if (!s) return false
  if (s.status === 'closed') return false
  return s.status === 'open' || s.is_current === true || !s.status
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

const stageTone = computed(() => stageVisual(item.value?.stage?.code || item.value?.stage?.name))

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
  if (days == null) return { tone: 'neutral', label: 'بدون مهلت', detail: '', days: null as number | null }
  if (days < 0) return { tone: 'danger', label: `${toFaDigits(Math.abs(days))}روز تأخیر`, detail: '', days }
  if (days === 0) return { tone: 'warn', label: 'امروز', detail: '', days }
  if (days <= 2) return { tone: 'warn', label: `${toFaDigits(days)}روز`, detail: '', days }
  if (days <= 7) return { tone: 'info', label: `${toFaDigits(days)}روز`, detail: '', days }
  return { tone: 'ok', label: `${toFaDigits(days)}روز`, detail: '', days }
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
  const recentActs = [...activities].sort((a: any, b: any) => Number(b.id || 0) - Number(a.id || 0)).slice(0, 2)

  let alert: string | null = null
  let scoreTone: 'ok' | 'warn' | 'danger' = 'ok'
  let scoreLabel = 'پایدار'

  if (overdue.length) {
    alert = `${toFaDigits(overdue.length)} گام از مهلت گذشته — امروز ببندید یا مهلت را تمدید کنید.`
    scoreTone = 'danger'
    scoreLabel = 'نیازمند توجه'
  } else if (currentDeadline.value.tone === 'warn' || currentDeadline.value.tone === 'danger') {
    alert = `مهلت گام جاری: ${currentDeadline.value.label}`
    scoreTone = currentDeadline.value.tone === 'danger' ? 'danger' : 'warn'
    scoreLabel = scoreTone === 'danger' ? 'نیازمند توجه' : 'حساس'
  } else if (!activities.length) {
    alert = 'هنوز فعالیتی ثبت نشده؛ مسیر فرصت هنوز روشن نیست.'
    scoreTone = 'warn'
    scoreLabel = 'حساس'
  }

  let suggestion = 'یک پیگیری کوتاه ثبت کنید و نتیجه را بنویسید.'
  if (overdue.length) suggestion = 'گام معوق را با نتیجه واقعی ببندید یا مهلت جدید بگذارید.'
  else if (!activities.length && currentStep.value) suggestion = `برای «${currentStep.value.title}» یک تماس یا جلسه ثبت کنید.`
  else if (currentDeadline.value.tone === 'warn') suggestion = 'امروز با مخاطب تماس بگیرید و وضعیت را مشخص کنید.'
  else if (closed.length && openSteps.length) suggestion = 'گام فعلی قابل ادامه است؛ گام بعدی را طراحی کنید.'
  else if (d.status === 'nurturing') suggestion = 'یک نمونه یا محتوای مفید برای مشتری بفرستید.'

  if (serverInsight.value?.suggestion) suggestion = serverInsight.value.suggestion
  if (serverInsight.value?.score_tone === 'danger' || serverInsight.value?.score_tone === 'warn') {
    scoreTone = serverInsight.value.score_tone
    scoreLabel = serverInsight.value.score_label || scoreLabel
  }

  return {
    scoreLabel,
    scoreTone,
    alert,
    focus: currentStep.value ? `گام جاری: ${currentStep.value.title}` : 'گام بازی تعریف نشده',
    metrics: [
      { label: 'وضعیت', value: statusLabel[d.status] || d.status },
      { label: 'مرحله', value: d.stage?.name || '—' },
      { label: 'گام باز', value: toFaDigits(openSteps.length) },
      { label: 'فعالیت', value: toFaDigits(activities.length) },
    ],
    recent: recentActs.length ? recentActs.map((a: any) => typeLabel[a.type] || a.subject || a.type).join('، ') : null,
    suggestion,
    source: 'local' as const,
  }
})

async function load() {
  if (!getCompany()?.id) { notifyError('شرکت جاری مشخص نشده است.'); return }
  loading.value = true
  try {
    const [res, stageRes] = await Promise.all([fetchOpportunity(id.value), fetchOpportunityStages()])
    item.value = res.data
    stages.value = (stageRes.data || []).map((s: any) => ({ title: s.name, value: s.id, code: s.code, raw: s }))
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
function goActivityNew() {
  if (!isViewingOpen.value) { notifyError('برای گام بسته‌شده نمی‌توان فعالیت ثبت کرد.'); return }
  router.push(`/opportunities/${id.value}/activities/new?from=steps`)
}
function selectStep(step: any) { selectedStepId.value = step?.id ?? null }
function openClose() {
  if (!isViewingOpen.value) { notifyError('این گام قبلاً بسته شده است.'); return }
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
function onAdminEditStep() { notifyError('ویرایش گام به‌زودی — فقط مدیر') }
function onAdminDeleteStep() { notifyError('حذف گام به‌زودی — فقط مدیر') }

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
        <VChip v-if="item.stage" size="small" variant="tonal" :color="stageTone.color === 'default' ? 'secondary' : stageTone.color" class="ms-auto">
          <VIcon start :icon="stageTone.icon" size="14" />
          {{ item.stage.name }}
        </VChip>
      </div>

      <VRow dense>
        <VCol cols="12" lg="8">
          <div v-if="currentStep || viewingStep" class="hero-step mb-3" :class="[`hero-step--${currentDeadline.tone}`, { 'hero-step--closed': !isViewingOpen }]">
            <div class="hero-step__body">
              <div class="hero-top d-flex flex-wrap align-center justify-space-between gap-2">
                <div class="min-w-0 flex-grow-1">
                  <div class="d-flex flex-wrap align-center gap-2 mb-1">
                    <span class="hero-badge">{{ isViewingOpen ? (selectedStepId && selectedStepId !== currentStep?.id ? 'انتخاب‌شده' : 'جاری') : 'بسته' }}</span>
                    <span v-if="isViewingOpen && currentDeadline.tone !== 'ok' && currentDeadline.tone !== 'neutral'" class="deadline-pill" :class="`deadline-pill--${currentDeadline.tone}`">{{ currentDeadline.label }}</span>
                  </div>
                  <h2 class="hero-title text-wrap mb-1">{{ viewingStep?.title || currentStep?.title }}</h2>
                  <div class="hero-meta text-caption text-medium-emphasis">
                    <span>{{ formatJalaliFa(viewingStep?.due_on || currentStep?.due_on) || 'بدون مهلت' }}</span>
                    <span class="hero-meta__dot">·</span>
                    <span :class="{ 'text-primary': isMyStep }">{{ assigneeLabel }}</span>
                  </div>
                </div>
                <div class="hero-actions d-flex align-center gap-1 flex-shrink-0">
                  <VBtn v-if="isViewingOpen" color="primary" size="small" variant="flat" prepend-icon="ri-checkbox-circle-line" @click="openClose">بستن گام</VBtn>
                  <VMenu v-if="canManageStep">
                    <template #activator="{ props }">
                      <VBtn v-bind="props" icon size="x-small" variant="text" color="secondary"><VIcon icon="ri-more-2-fill" size="18" /></VBtn>
                    </template>
                    <VList density="compact">
                      <VListItem title="ویرایش گام" prepend-icon="ri-edit-line" @click="onAdminEditStep" />
                      <VListItem title="حذف گام" prepend-icon="ri-delete-bin-line" class="text-error" @click="onAdminDeleteStep" />
                    </VList>
                  </VMenu>
                </div>
              </div>
            </div>
          </div>

          <VCard class="mb-3 main-card"><VCardText class="pa-4">
            <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
              <div class="text-subtitle-2 font-weight-medium d-flex align-center gap-2"><span class="section-orb section-orb--primary" />فعالیت‌های این گام</div>
              <VBtn v-if="isViewingOpen" color="primary" size="small" variant="tonal" prepend-icon="ri-add-line" @click="goActivityNew">ثبت فعالیت</VBtn>
              <span v-else class="text-caption text-medium-emphasis">گام بسته — فقط مشاهده</span>
            </div>
            <div v-if="!stepActivities.length" class="empty-soft">هنوز فعالیتی برای این گام ثبت نشده.</div>
            <div v-for="a in stepActivities" :key="a.id" class="activity-item">
              <div class="activity-item__bar" :style="{ background: activityBarColor(a.type) }" />
              <div class="activity-item__icon" :style="{ color: activityBarColor(a.type) }"><VIcon :icon="activityVisual(a.type).icon" size="18" /></div>
              <div class="min-w-0 flex-grow-1">
                <div class="d-flex flex-wrap align-center gap-2 mb-1">
                  <VChip size="x-small" variant="tonal" :color="activityVisual(a.type).color === 'default' ? 'secondary' : activityVisual(a.type).color">{{ typeLabel[a.type] || a.subject || a.type }}</VChip>
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
                <tr v-for="s in allSteps" :key="s.id" class="steps-table__row" :class="{ 'steps-table__row--active': selectedStepId === s.id, 'steps-table__row--closed': s.status === 'closed' }" @click="selectStep(s)">
                  <td class="font-weight-medium">
                    <span class="d-inline-flex align-center gap-1">
                      <VIcon :icon="s.status === 'closed' ? 'ri-checkbox-circle-line' : 'ri-play-circle-line'" size="16" :color="s.status === 'closed' ? 'success' : 'primary'" />
                      {{ s.title }}
                    </span>
                  </td>
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
                <div class="text-body-2 d-flex align-center gap-1 text-medium-emphasis"><VIcon icon="ri-pulse-line" size="18" color="primary" />وضعیت فرصت</div>
                <VChip size="small" variant="flat" :color="smartInsight.scoreTone === 'danger' ? 'error' : smartInsight.scoreTone === 'warn' ? 'warning' : 'success'">{{ smartInsight.scoreLabel }}</VChip>
              </div>
              <div v-if="smartInsight.alert" class="insight-alert mb-3" :class="`insight-alert--${smartInsight.scoreTone}`">
                <VIcon :icon="smartInsight.scoreTone === 'ok' ? 'ri-information-line' : 'ri-error-warning-line'" size="18" class="flex-shrink-0 mt-1" />
                <span>{{ smartInsight.alert }}</span>
              </div>
              <div class="insight-metrics mb-3">
                <div v-for="(m, i) in smartInsight.metrics" :key="i" class="insight-metric">
                  <span class="insight-metric__label">{{ m.label }}</span>
                  <span class="insight-metric__value">{{ m.value }}</span>
                </div>
              </div>
              <div class="insight-focus text-caption text-medium-emphasis mb-1">{{ smartInsight.focus }}</div>
              <div v-if="smartInsight.recent" class="text-caption text-disabled mb-3">اخیر: {{ smartInsight.recent }}</div>
              <div class="insight-action">
                <div class="insight-action__label">کار پیشنهادی</div>
                <div class="insight-action__text">{{ smartInsight.suggestion }}</div>
              </div>
            </VCardText>
          </VCard>

          <VCard class="mb-3 main-card"><VCardText class="pa-4">
            <div class="text-subtitle-2 font-weight-medium mb-3 d-flex align-center gap-2"><span class="section-orb section-orb--info" />خلاصه فرصت</div>
            <div class="text-body-2 mb-2"><span class="text-medium-emphasis">طرف:</span> {{ item.organization_name }}</div>
            <div class="text-body-2 mb-2 d-flex align-center gap-1">
              <span class="text-medium-emphasis">مرحله:</span>
              <VChip v-if="item.stage" size="x-small" variant="tonal" :color="stageTone.color === 'default' ? 'secondary' : stageTone.color">
                <VIcon start :icon="stageTone.icon" size="12" />{{ item.stage.name }}
              </VChip>
              <span v-else>—</span>
            </div>
            <div class="text-body-2 mb-2"><span class="text-medium-emphasis">وضعیت:</span> {{ statusLabel[item.status] || item.status }}</div>
            <div class="text-body-2"><span class="text-medium-emphasis">مسئول:</span> {{ item.assignee?.name || item.owner?.name || '—' }}</div>
          </VCardText></VCard>
        </VCol>
      </VRow>

      <FormDrawer v-model="closeOpen" title="بستن گام" :dirty="dirty" :saving="closing" @submit="submitClose">
        <VForm ref="formRef" @submit.prevent="submitClose">
          <VTextarea v-model="closeForm.result" label="نتیجه گام" :rules="[rules.required]" rows="3" class="mb-3" />
          <VSelect v-model="closeForm.status" :items="Object.entries(statusLabel).map(([value, title]) => ({ title, value }))" label="وضعیت فرصت" class="mb-3" />
          <VSelect v-model="closeForm.stage_id" :items="stages" label="مرحله قیف" clearable class="mb-3" />
          <VTextField v-model="closeForm.next_step_title" label="عنوان گام بعدی (اختیاری)" class="mb-3" />
          <JalaliDateField v-model="closeForm.next_step_due_on" label="مهلت گام بعدی" />
        </VForm>
      </FormDrawer>
    </template>
  </div>
</template>

<style scoped>
.steps-workspace { max-width: 1200px; }
.page-title { font-size: 1.05rem !important; }
.crumb-btn { background: none; border: 0; padding: 0; color: inherit; cursor: pointer; }
.crumb-btn:hover { color: rgb(var(--v-theme-primary)); }
.main-card { border-radius: 12px !important; }
.section-orb { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.section-orb--primary { background: rgb(var(--v-theme-primary)); }
.section-orb--secondary { background: rgb(var(--v-theme-secondary)); }
.section-orb--info { background: rgb(var(--v-theme-info)); }
.empty-soft { padding: 1rem; border-radius: 10px; background: rgba(var(--v-theme-on-surface), .03); color: rgba(var(--v-theme-on-surface), .55); font-size: .85rem; }
.activity-item { display: flex; gap: .55rem; padding: .65rem 0; border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
.activity-item:last-child { border-bottom: 0; }
.activity-item__bar { width: 3px; border-radius: 3px; flex-shrink: 0; }
.activity-item__icon { flex-shrink: 0; opacity: .9; }
.steps-table__row { cursor: pointer; }
.steps-table__row--active { background: rgba(var(--v-theme-primary), .06); }
.steps-table__row--closed { opacity: .72; }
.deadline-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-inline-end: .35rem; background: #9e9e9e; }
.deadline-dot--danger { background: #e53935; }
.deadline-dot--warn { background: #fb8c00; }
.deadline-dot--info { background: #1e88e5; }
.deadline-dot--ok { background: #43a047; }
.hero-step { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); background: rgb(var(--v-theme-surface)); }
.hero-step::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 3px; background: rgba(var(--v-theme-on-surface), .18); }
.hero-step--danger::before { background: rgba(var(--v-theme-error), .65); }
.hero-step--warn::before { background: rgba(var(--v-theme-warning), .7); }
.hero-step--info::before { background: rgba(var(--v-theme-info), .55); }
.hero-step--ok::before { background: rgba(var(--v-theme-success), .5); }
.hero-step--closed { opacity: .92; }
.hero-step--closed::before { background: rgba(var(--v-theme-on-surface), .25); }
.hero-step__body { padding: .7rem .85rem; }
.hero-badge { display: inline-flex; padding: .08rem .4rem; border-radius: 6px; font-size: .68rem; background: rgba(var(--v-theme-primary), .1); color: rgb(var(--v-theme-primary)); }
.hero-title { font-size: .95rem !important; font-weight: 500 !important; line-height: 1.35 !important; margin: 0; }
.hero-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem; line-height: 1.3; }
.hero-meta__dot { opacity: .4; }
.deadline-pill { display: inline-flex; padding: .08rem .35rem; border-radius: 6px; font-size: .68rem; font-weight: 400; opacity: .85; }
.deadline-pill--danger { background: rgba(var(--v-theme-error), .1); color: rgba(var(--v-theme-error), .8); }
.deadline-pill--warn { background: rgba(var(--v-theme-warning), .12); color: rgba(var(--v-theme-on-surface), .65); }
.deadline-pill--info { background: rgba(var(--v-theme-info), .1); color: rgba(var(--v-theme-on-surface), .6); }
.deadline-pill--ok { background: rgba(var(--v-theme-success), .1); color: rgba(var(--v-theme-on-surface), .55); }
.deadline-pill--neutral { background: rgba(var(--v-theme-on-surface), .06); color: rgba(var(--v-theme-on-surface), .45); }
.insight-card { border: 1px solid rgba(var(--v-theme-primary), .16); background: rgb(var(--v-theme-surface)); border-radius: 12px !important; }
.insight-alert { display: flex; gap: .45rem; align-items: flex-start; padding: .55rem .65rem; border-radius: 8px; font-size: .8rem; line-height: 1.45; }
.insight-alert--danger { background: rgba(var(--v-theme-error), .1); color: rgb(var(--v-theme-error)); }
.insight-alert--warn { background: rgba(var(--v-theme-warning), .12); color: rgba(var(--v-theme-on-surface), .8); }
.insight-alert--ok { background: rgba(var(--v-theme-success), .1); color: rgba(var(--v-theme-on-surface), .75); }
.insight-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; }
.insight-metric { min-width: 0; padding: .4rem .5rem; border-radius: 8px; background: rgba(var(--v-theme-on-surface), .04); }
.insight-metric__label { display: block; font-size: .68rem; color: rgba(var(--v-theme-on-surface), .5); margin-bottom: .1rem; }
.insight-metric__value { display: block; font-size: .8rem; line-height: 1.3; overflow-wrap: anywhere; }
.insight-focus { line-height: 1.35; }
.insight-action { padding: .65rem .75rem; border-radius: 8px; background: rgba(var(--v-theme-primary), .07); border-inline-start: 3px solid rgb(var(--v-theme-primary)); }
.insight-action__label { font-size: .68rem; color: rgba(var(--v-theme-on-surface), .5); margin-bottom: .3rem; }
.insight-action__text { font-size: .85rem; line-height: 1.45; overflow-wrap: anywhere; }
</style>
