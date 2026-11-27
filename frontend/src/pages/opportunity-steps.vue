<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import { useOpportunityInsight } from '@/composables/useOpportunityInsight'
import { $api } from '@/utils/api'
import { formatJalaliFa, toFaDigits } from '@/utils/fa'
import { activityVisual, stageVisual } from '@/utils/opportunityVisuals'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotify()

const id = computed(() => Number(route.params.id))
const loading = ref(true)
const item = ref<any>(null)
const stages = ref<any[]>([])
const users = ref<any[]>([])
const expandedSteps = ref<Set<number>>(new Set())
const activityDetailOpen = ref(false)
const activityDetail = ref<any>(null)
const closeOpen = ref(false)
const assignOpen = ref(false)
const actionSaving = ref(false)

const closeForm = ref({
  result: '',
  stage_id: null as number | null,
  status: 'active',
  next_step_title: '',
  next_step_due_on: null as string | null,
  assignee_user_id: null as number | null,
  return_to_owner: false,
})

const assignForm = ref({
  to_user_id: null as number | null,
  step_title: '',
  step_due_on: null as string | null,
  priority: 'normal',
  note: '',
})

const typeLabel: Record<string, string> = {
  call: 'تماس',
  meeting: 'جلسه',
  email: 'ایمیل / پیام',
  note: 'یادداشت',
  visit: 'بازدید',
  other: 'سایر',
}
const priorityLabel: Record<string, string> = {
  low: 'کم',
  normal: 'عادی',
  high: 'بالا',
  urgent: 'فوری',
}
const statusItems = [
  { title: 'فعال', value: 'active' },
  { title: 'پرورش', value: 'nurturing' },
  { title: 'متوقف', value: 'paused' },
  { title: 'موفق', value: 'won' },
  { title: 'ناموفق', value: 'lost' },
]
const priorityItems = [
  { title: 'کم', value: 'low' },
  { title: 'عادی', value: 'normal' },
  { title: 'بالا', value: 'high' },
  { title: 'فوری', value: 'urgent' },
]

const currentUserId = computed(() => {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    return Number(u?.id || 0)
  } catch { return 0 }
})

const isOwner = computed(() => item.value && currentUserId.value && Number(item.value.owner?.id) === currentUserId.value)
const currentStep = computed(() => (item.value?.steps || []).find((s: any) => s.is_current) || null)
const isViewingOpen = computed(() => currentStep.value && currentStep.value.status !== 'closed')
const historySteps = computed(() => (item.value?.steps || []).filter((s: any) => !s.is_current))
const activities = computed(() => item.value?.activities || [])
const referrals = computed(() => item.value?.referrals || [])
const currentActivities = computed(() => {
  const sid = currentStep.value?.id
  if (!sid) return activities.value
  return activities.value.filter((a: any) => a.step_id === sid)
})

const stageTone = computed(() => stageVisual(item.value?.stage?.code || item.value?.stage?.name))
const { serverInsight, loadInsight } = useOpportunityInsight()
const smartInsight = computed(() => {
  if (!item.value) return null
  if (serverInsight.value && (serverInsight.value.source === 'llm' || serverInsight.value.bullets?.length)) {
    const bullets = (serverInsight.value.bullets && serverInsight.value.bullets.length)
      ? serverInsight.value.bullets
      : [serverInsight.value.suggestion || 'تحلیل مدل آماده است']
    return { source: 'llm' as const, bullets, suggestion: serverInsight.value.suggestion }
  }
  const openSteps = (item.value.steps || []).filter((s: any) => s.status !== 'closed').length
  const acts = (item.value.activities || []).length
  const bullets: string[] = []
  if (openSteps <= 1 && acts < 2) bullets.push('گام جاری تازه است؛ حداقل یک تماس یا جلسه ثبت کنید.')
  if (currentStep.value?.due_on) {
    const due = new Date(currentStep.value.due_on)
    const days = Math.ceil((due.getTime() - Date.now()) / 86400000)
    if (days < 0) bullets.push('مهلت گام جاری گذشته است.')
    else if (days <= 2) bullets.push('مهلت گام جاری نزدیک است.')
  }
  if (!bullets.length) bullets.push('وضعیت پایدار است؛ مسیر فروش را با فعالیت منظم جلو ببرید.')
  return {
    source: 'local' as const,
    tone: bullets.some(b => b.includes('گذشته')) ? 'danger' : bullets.some(b => b.includes('نزدیک')) ? 'warn' : 'ok',
    bullets,
    metrics: {
      status: item.value.status === 'active' ? 'فعال' : item.value.status,
      stage: item.value.stage?.name || '—',
      openSteps: String(openSteps),
      activities: String(acts),
      current: currentStep.value?.title || '—',
    },
  }
})

function activitiesOfStep(stepId: number) {
  return activities.value.filter((a: any) => a.step_id === stepId)
}
function isExpanded(stepId: number) {
  return expandedSteps.value.has(stepId)
}
function toggleStepExpand(stepId: number) {
  const next = new Set(expandedSteps.value)
  if (next.has(stepId)) next.delete(stepId)
  else next.add(stepId)
  expandedSteps.value = next
}
function openActivityDetail(a: any) {
  activityDetail.value = a
  activityDetailOpen.value = true
}
function activityBarColor(type?: string) {
  return activityVisual(type).color === 'default' ? 'rgb(var(--v-theme-primary))' : `rgb(var(--v-theme-${activityVisual(type).color}))`
}

const currentDeadline = computed(() => {
  const due = currentStep.value?.due_on
  if (!due) return { tone: 'ok', label: '' }
  const days = Math.ceil((new Date(due).getTime() - Date.now()) / 86400000)
  if (days < 0) return { tone: 'danger', label: `${toFaDigits(Math.abs(days))} روز تأخیر` }
  if (days <= 2) return { tone: 'warn', label: `${toFaDigits(days)} روز مانده` }
  return { tone: 'ok', label: `${toFaDigits(days)} روز` }
})

function goBack() {
  const from = String(route.query.from || '')
  if (from === 'list') router.push({ name: 'opportunities' })
  else if (from === 'detail' || from === 'activity') router.push({ name: 'opportunity-detail', params: { id: id.value } })
  else if (window.history.length > 1) router.back()
  else router.push({ name: 'opportunities' })
}
function goDashboard() { router.push({ name: 'dashboard' }) }
function goList() { router.push({ name: 'opportunities' }) }
function goActivityCreate() {
  router.push({ name: 'opportunity-activity-create', params: { id: id.value }, query: { from: 'steps' } })
}

async function load() {
  loading.value = true
  try {
    const [detail, st, us] = await Promise.all([
      $api(`/opportunities/${id.value}`),
      $api('/opportunities/stages'),
      $api('/users').catch(() => ({ data: [] })),
    ])
    item.value = detail.data
    stages.value = st.data || []
    users.value = (us.data || us || []).map((u: any) => ({ title: u.name, value: u.id }))
    loadInsight(id.value)
  }
  catch (e: any) { notifyError(e?.message || 'بارگذاری ناموفق') }
  finally { loading.value = false }
}

function openClose() {
  closeForm.value = {
    result: '',
    stage_id: item.value?.stage?.id || null,
    status: item.value?.status || 'active',
    next_step_title: '',
    next_step_due_on: null,
    assignee_user_id: item.value?.owner?.id || null,
    return_to_owner: !isOwner.value,
  }
  closeOpen.value = true
}
function openAssign() {
  assignForm.value = {
    to_user_id: null,
    step_title: currentStep.value?.title ? `پیگیری: ${currentStep.value.title}` : '',
    step_due_on: null,
    priority: 'normal',
    note: '',
  }
  assignOpen.value = true
}

async function submitClose() {
  if (!closeForm.value.result?.trim()) {
    notifyError('نتیجه بستن گام را بنویسید')
    return
  }
  actionSaving.value = true
  try {
    const res = await $api(`/opportunities/${id.value}/close-step`, {
      method: 'POST',
      body: {
        result: closeForm.value.result,
        stage_id: closeForm.value.stage_id,
        status: closeForm.value.status,
        next_step_title: closeForm.value.next_step_title || null,
        next_step_due_on: closeForm.value.next_step_due_on,
        assignee_user_id: closeForm.value.assignee_user_id,
        return_to_owner: closeForm.value.return_to_owner,
      },
    })
    item.value = res.data
    closeOpen.value = false
    success('گام بسته شد')
  }
  catch (e: any) { notifyError(e?.message || 'بستن گام ناموفق بود') }
  finally { actionSaving.value = false }
}

async function submitAssign() {
  if (!assignForm.value.to_user_id) {
    notifyError('کاربر مقصد را انتخاب کنید')
    return
  }
  if (!assignForm.value.step_title?.trim()) {
    notifyError('عنوان ماموریت / گام الزامی است')
    return
  }
  actionSaving.value = true
  try {
    const res = await $api(`/opportunities/${id.value}/assign`, {
      method: 'POST',
      body: {
        to_user_id: assignForm.value.to_user_id,
        step_title: assignForm.value.step_title,
        step_due_on: assignForm.value.step_due_on,
        priority: assignForm.value.priority,
        note: assignForm.value.note || null,
      },
    })
    item.value = res.data
    assignOpen.value = false
    success('ارجاع انجام شد؛ ماموریت به‌عنوان گام جاری ثبت شد')
  }
  catch (e: any) { notifyError(e?.message || 'ارجاع ناموفق بود') }
  finally { actionSaving.value = false }
}

onMounted(load)
</script>

<template>
  <div class="steps-workspace">
    <div v-if="loading" class="text-center py-10 text-medium-emphasis">در حال بارگذاری...</div>
    <template v-else-if="item">
      <div class="page-breadcrumb d-flex flex-wrap align-center gap-1 text-caption text-medium-emphasis mb-1">
        <button type="button" class="crumb-btn" @click="goDashboard">خانه</button>
        <VIcon icon="ri-arrow-left-s-line" size="14" />
        <button type="button" class="crumb-btn" @click="goList">فرصت‌ها</button>
        <VIcon icon="ri-arrow-left-s-line" size="14" />
        <span class="text-truncate">{{ item.organization_name }}</span>
      </div>

      <div class="page-head d-flex flex-wrap align-center gap-2 mb-2">
        <VBtn icon variant="text" color="secondary" size="small" density="comfortable" @click="goBack">
          <VIcon icon="ri-arrow-right-s-line" size="26" />
        </VBtn>
        <h1 class="page-title text-h6 font-weight-medium mb-0 text-wrap flex-grow-1">
          مدیریت گام‌های «{{ item.organization_name }}»
        </h1>
        <VChip
          v-if="item.stage"
          size="small"
          variant="tonal"
          :color="stageTone.color === 'default' ? 'secondary' : stageTone.color"
          class="d-none d-sm-inline-flex flex-shrink-0"
        >
          <VIcon start :icon="stageTone.icon" size="14" />
          {{ item.stage.name }}
        </VChip>
      </div>

      <VRow dense>
        <VCol cols="12" lg="8">
          <div
            v-if="currentStep"
            class="hero-step mb-2"
            :class="[`hero-step--${currentDeadline.tone}`, { 'hero-step--closed': !isViewingOpen }]"
          >
            <div class="hero-step__body">
              <div class="d-flex flex-wrap align-center justify-space-between gap-2">
                <div class="min-w-0 flex-grow-1">
                  <div class="d-flex flex-wrap align-center gap-2 mb-1">
                    <span class="hero-badge">گام جاری</span>
                    <span
                      v-if="isViewingOpen && currentDeadline.tone !== 'ok' && currentDeadline.label"
                      class="deadline-pill"
                      :class="`deadline-pill--${currentDeadline.tone}`"
                    >{{ currentDeadline.label }}</span>
                    <span v-else-if="isViewingOpen && currentDeadline.label" class="text-caption text-medium-emphasis">{{ currentDeadline.label }}</span>
                  </div>
                  <h2 class="hero-title text-truncate">{{ currentStep.title }}</h2>
                  <div class="hero-meta text-medium-emphasis mt-1">
                    <span v-if="currentStep.due_on">{{ formatJalaliFa(currentStep.due_on) }}</span>
                    <span v-if="currentStep.due_on && currentStep.assignee" class="hero-meta__dot">·</span>
                    <span v-if="currentStep.assignee">
                      {{ Number(currentStep.assignee.id) === currentUserId ? 'خودت' : currentStep.assignee.name }}
                    </span>
                  </div>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                  <VBtn
                    v-if="isViewingOpen"
                    color="primary"
                    size="small"
                    @click="openClose"
                  >
                    <VIcon start icon="ri-checkbox-circle-line" size="16" />
                    بستن گام
                  </VBtn>
                </div>
              </div>
            </div>
          </div>

          <VCard class="mb-2 main-card">
            <VCardText class="pa-2 pa-sm-3">
              <div class="d-flex align-center justify-space-between gap-2 mb-2">
                <div class="text-subtitle-2 font-weight-medium">فعالیت‌های گام جاری</div>
                <VBtn
                  v-if="isViewingOpen"
                  size="small"
                  color="primary"
                  variant="tonal"
                  @click="goActivityCreate"
                >
                  <VIcon start icon="ri-add-line" size="16" />
                  ثبت فعالیت
                </VBtn>
              </div>
              <div v-if="!currentActivities.length" class="empty-soft">هنوز فعالیتی برای این گام ثبت نشده.</div>
              <div v-else class="d-flex flex-column gap-1">
                <button
                  v-for="a in currentActivities"
                  :key="a.id"
                  type="button"
                  class="expand-act"
                  @click="openActivityDetail(a)"
                >
                  <span class="act-dot" :style="{ background: activityBarColor(a.type) }" />
                  <VIcon :icon="activityVisual(a.type).icon" size="16" />
                  <span class="text-body-2 text-truncate">{{ typeLabel[a.type] || a.subject || a.type }}</span>
                  <span class="text-caption text-medium-emphasis ms-auto flex-shrink-0">{{ formatJalaliFa(a.happened_at || a.created_at) }}</span>
                </button>
              </div>
            </VCardText>
          </VCard>

          <VCard class="mb-2 main-card">
            <VCardText class="pa-2 pa-sm-3">
              <div class="text-subtitle-2 font-weight-medium mb-2">گام‌های قبلی</div>
              <div v-if="!historySteps.length" class="empty-soft">گام قبلی ثبت نشده است.</div>

              <div v-else class="d-none d-md-block table-wrap">
                <table class="steps-table">
                  <thead>
                    <tr>
                      <th style="width:2rem" />
                      <th>عنوان</th>
                      <th style="width:5rem">وضعیت</th>
                      <th style="width:8rem">تاریخ بستن</th>
                      <th style="width:4rem">فعالیت</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="s in historySteps" :key="s.id">
                      <tr class="steps-table__row" @click="toggleStepExpand(s.id)">
                        <td>
                          <VIcon :icon="isExpanded(s.id) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" size="18" />
                        </td>
                        <td>
                          <div class="d-flex align-center gap-1">
                            <VIcon icon="ri-checkbox-circle-fill" size="16" color="success" />
                            <span class="text-body-2">{{ s.title }}</span>
                          </div>
                        </td>
                        <td><VChip size="x-small" variant="tonal" color="secondary">بسته</VChip></td>
                        <td class="text-caption">{{ s.status === 'closed' ? (formatJalaliFa(s.closed_at) || '—') : '—' }}</td>
                        <td class="text-caption">{{ toFaDigits(activitiesOfStep(s.id).length) }}</td>
                      </tr>
                      <tr v-if="isExpanded(s.id)" class="steps-table__expand">
                        <td colspan="5">
                          <div v-if="s.result" class="text-caption text-medium-emphasis mb-2">نتیجه: {{ s.result }}</div>
                          <div v-if="!activitiesOfStep(s.id).length" class="text-caption text-medium-emphasis pa-2">بدون فعالیت</div>
                          <div v-else>
                            <button
                              v-for="a in activitiesOfStep(s.id)"
                              :key="a.id"
                              type="button"
                              class="expand-act"
                              @click.stop="openActivityDetail(a)"
                            >
                              <VIcon :icon="activityVisual(a.type).icon" size="14" />
                              <span class="text-truncate">{{ typeLabel[a.type] || a.subject || a.type }}</span>
                              <span class="text-caption text-medium-emphasis">{{ formatJalaliFa(a.happened_at || a.created_at) }}</span>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              <div v-if="historySteps.length" class="d-md-none d-flex flex-column gap-2">
                <div v-for="s in historySteps" :key="s.id" class="step-card" @click="toggleStepExpand(s.id)">
                  <div class="d-flex align-center justify-space-between gap-2">
                    <div class="min-w-0">
                      <div class="text-body-2 font-weight-medium text-truncate">{{ s.title }}</div>
                      <div class="text-caption text-medium-emphasis">
                        بسته · {{ formatJalaliFa(s.closed_at) || '—' }} · {{ toFaDigits(activitiesOfStep(s.id).length) }} فعالیت
                      </div>
                    </div>
                    <VIcon :icon="isExpanded(s.id) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" size="18" />
                  </div>
                  <div v-if="isExpanded(s.id)" class="mt-2">
                    <div v-if="s.result" class="text-caption mb-1">نتیجه: {{ s.result }}</div>
                    <button
                      v-for="a in activitiesOfStep(s.id)"
                      :key="a.id"
                      type="button"
                      class="expand-act"
                      @click.stop="openActivityDetail(a)"
                    >
                      <VIcon :icon="activityVisual(a.type).icon" size="14" />
                      <span class="text-truncate">{{ typeLabel[a.type] || a.subject || a.type }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </VCardText>
          </VCard>

          <VCard v-if="referrals.length" class="mb-2 main-card">
            <VCardText class="pa-2 pa-sm-3">
              <div class="text-subtitle-2 font-weight-medium mb-2">گردش و ارجاع فرصت</div>
              <div class="d-none d-md-block table-wrap">
                <table class="steps-table">
                  <thead>
                    <tr><th>از</th><th>به</th><th>ماموریت</th><th>اولویت</th><th>تاریخ</th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="r in referrals" :key="r.id">
                      <td class="text-body-2">{{ r.from_user?.name || '—' }}</td>
                      <td class="text-body-2">{{ r.to_user?.name || '—' }}</td>
                      <td class="text-body-2">{{ r.step_title || '—' }}</td>
                      <td class="text-caption">{{ priorityLabel[r.priority] || r.priority || '—' }}</td>
                      <td class="text-caption">{{ formatJalaliFa(r.created_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="d-md-none d-flex flex-column gap-2">
                <div v-for="r in referrals" :key="r.id" class="step-card step-card--static">
                  <div class="text-body-2 font-weight-medium">{{ r.from_user?.name || '—' }} → {{ r.to_user?.name || '—' }}</div>
                  <div class="text-caption text-medium-emphasis">{{ r.step_title || '—' }}</div>
                  <div class="text-caption">{{ formatJalaliFa(r.created_at) }} · {{ priorityLabel[r.priority] || '' }}</div>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" lg="4">
          <VCard v-if="smartInsight" class="mb-2 insight-card">
            <VCardText class="pa-2 pa-sm-3">
              <div class="d-flex align-center justify-space-between gap-2 mb-2">
                <div class="text-body-2 d-flex align-center gap-1 text-medium-emphasis">
                  <VIcon icon="ri-pulse-line" size="16" />
                  وضعیت و برآیند
                </div>
                <VChip size="x-small" :color="smartInsight.tone === 'danger' ? 'error' : smartInsight.tone === 'warn' ? 'warning' : 'success'" variant="tonal">
                  <template v-if="smartInsight.source === 'llm'">تحلیل با مدل زبانی</template>
                  <template v-else>تحلیل محلی</template>
                </VChip>
              </div>
              <div
                v-for="(b, i) in smartInsight.bullets"
                :key="i"
                class="insight-alert mb-2"
                :class="smartInsight.tone === 'danger' ? 'insight-alert--danger' : smartInsight.tone === 'warn' ? 'insight-alert--warn' : ''"
              >
                <VIcon icon="ri-information-line" size="16" class="flex-shrink-0 mt-1" />
                <span>{{ b }}</span>
              </div>
              <div v-if="smartInsight.metrics" class="insight-metrics">
                <div class="insight-metric">
                  <span class="insight-metric__label">وضعیت</span>
                  <span class="insight-metric__value">{{ smartInsight.metrics.status }}</span>
                </div>
                <div class="insight-metric">
                  <span class="insight-metric__label">مرحله قیف</span>
                  <span class="insight-metric__value">{{ smartInsight.metrics.stage }}</span>
                </div>
                <div class="insight-metric">
                  <span class="insight-metric__label">گام باز</span>
                  <span class="insight-metric__value">{{ toFaDigits(smartInsight.metrics.openSteps) }}</span>
                </div>
                <div class="insight-metric">
                  <span class="insight-metric__label">فعالیت</span>
                  <span class="insight-metric__value">{{ toFaDigits(smartInsight.metrics.activities) }}</span>
                </div>
              </div>
              <div v-if="smartInsight.metrics?.current" class="text-caption text-medium-emphasis mt-2">
                گام جاری: {{ smartInsight.metrics.current }}
              </div>
            </VCardText>
          </VCard>

          <VCard class="mb-2 main-card">
            <VCardText class="pa-2 pa-sm-3">
              <div class="text-subtitle-2 font-weight-medium mb-2">خلاصه فرصت</div>
              <div class="text-body-2 mb-1">طرف: {{ item.organization_name }}</div>
              <div class="text-body-2 mb-1">مالک: {{ item.owner?.name || '—' }}</div>
              <div class="text-body-2 mb-1">مسئول فعلی: {{ item.assignee?.name || item.owner?.name || '—' }}</div>
              <div class="text-body-2 mb-1">وضعیت: {{ item.status === 'active' ? 'فعال' : item.status }}</div>
              <div class="text-body-2">مرحله قیف: {{ item.stage?.name || '—' }}</div>
            </VCardText>
          </VCard>

          <VBtn block color="secondary" variant="tonal" class="mb-2" @click="openAssign">
            <VIcon start icon="ri-share-forward-line" size="16" />
            ارجاع فرصت
          </VBtn>
        </VCol>
      </VRow>
    </template>
  </div>

  <FormDrawer v-model="closeOpen" title="بستن گام جاری" :subtitle="currentStep?.title || ''" width="420">
    <VForm @submit.prevent="submitClose">
      <div class="form-section-label mb-1">چه کاری انجام شد و خروجی چه بود؟</div>
      <VTextarea v-model="closeForm.result" rows="3" auto-grow density="compact" class="mb-3" hide-details="auto" />

      <div class="form-section-label mb-1">مرحله فروش در قیف</div>
      <div class="text-caption text-medium-emphasis mb-1">مثلاً سرنخ، پیشنهاد، مذاکره — مسیر فروش در قیف را نشان می‌دهد.</div>
      <VSelect
        v-model="closeForm.stage_id"
        :items="stages.map(s => ({ title: s.name, value: s.id }))"
        label="مرحله قیف"
        density="compact"
        class="mb-3"
        hide-details="auto"
      />

      <div class="form-section-label mb-1">وضعیت کلی فرصت</div>
      <div class="text-caption text-medium-emphasis mb-1">آیا فرصت هنوز فعال است یا نهایی شده؟ فقط مالک می‌تواند موفق/ناموفق ببندد.</div>
      <VSelect
        v-model="closeForm.status"
        :items="statusItems"
        label="وضعیت فرصت"
        density="compact"
        class="mb-3"
        hide-details="auto"
        :disabled="!isOwner && ['won','lost'].includes(closeForm.status)"
      />

      <template v-if="isOwner">
        <div class="form-section-label mb-1">گام بعدی (اختیاری — فقط مالک)</div>
        <VTextField v-model="closeForm.next_step_title" label="عنوان گام بعدی" density="compact" class="mb-2" hide-details="auto" />
        <JalaliDateField v-model="closeForm.next_step_due_on" label="مهلت گام بعدی" density="compact" class="mb-2" />
        <VSelect
          v-model="closeForm.assignee_user_id"
          :items="users"
          label="مسئول گام بعدی"
          density="compact"
          class="mb-2"
          clearable
          hide-details="auto"
        />
      </template>
      <template v-else>
        <VAlert type="info" variant="tonal" density="compact" class="mb-2">
          با بستن این ماموریت، فرصت به مالک برمی‌گردد.
        </VAlert>
      </template>

      <div class="d-flex justify-end gap-2 mt-4">
        <VBtn variant="text" @click="closeOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="actionSaving" @click="submitClose">ثبت و بستن گام</VBtn>
      </div>
    </VForm>
  </FormDrawer>

  <FormDrawer v-model="assignOpen" title="ارجاع فرصت" :subtitle="item?.organization_name || ''" width="420">
    <VForm @submit.prevent="submitAssign">
      <div class="text-caption text-medium-emphasis mb-3">
        ماموریت به‌صورت یک گام جاری برای کاربر مقصد ثبت می‌شود. او فقط زیر همان گام فعالیت ثبت می‌کند؛ تعریف گام بعدی و بستن نهایی فرصت با مالک است.
      </div>
      <VSelect
        v-model="assignForm.to_user_id"
        :items="users"
        label="ارجاع به *"
        density="compact"
        class="mb-3"
        hide-details="auto"
      />
      <VTextField
        v-model="assignForm.step_title"
        label="عنوان ماموریت / گام *"
        density="compact"
        class="mb-1"
        hide-details="auto"
      />
      <div class="text-caption text-medium-emphasis mb-3">همین عنوان گام جاری مقصد می‌شود</div>
      <JalaliDateField v-model="assignForm.step_due_on" label="مهلت ماموریت" density="compact" class="mb-3" />
      <VSelect
        v-model="assignForm.priority"
        :items="priorityItems"
        label="اولویت"
        density="compact"
        class="mb-3"
        hide-details="auto"
      />
      <VTextarea v-model="assignForm.note" label="توضیح برای مقصد" rows="3" density="compact" class="mb-3" hide-details="auto" />
      <div class="d-flex justify-end gap-2 mt-4">
        <VBtn variant="text" @click="assignOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="actionSaving" @click="submitAssign">ثبت ارجاع</VBtn>
      </div>
    </VForm>
  </FormDrawer>

  <VDialog v-model="activityDetailOpen" max-width="520" scrollable>
    <VCard v-if="activityDetail">
      <VCardTitle class="text-body-1">{{ typeLabel[activityDetail.type] || activityDetail.subject || 'جزئیات فعالیت' }}</VCardTitle>
      <VCardSubtitle>{{ formatJalaliFa(activityDetail.happened_at || activityDetail.created_at) }}</VCardSubtitle>
      <VCardText>
        <div class="text-body-2 text-wrap" style="white-space: pre-wrap">{{ activityDetail.body || '—' }}</div>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn variant="text" @click="activityDetailOpen = false">بستن</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.steps-workspace{
  max-width:1200px;
  width:100%;
  overflow-x:hidden;
  padding-bottom:.5rem;
}
.page-title{font-size:1.05rem!important;line-height:1.35!important;font-weight:500!important}
.crumb-btn{border:0;background:transparent;color:inherit;cursor:pointer;padding:0;font:inherit}
.crumb-btn:hover{color:rgb(var(--v-theme-primary))}
.main-card{border-radius:12px!important}
.table-wrap{width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch}
.steps-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:.8125rem}
.steps-table th{padding:.35rem .35rem;font-weight:500;color:rgba(var(--v-theme-on-surface),.55);border-bottom:1px solid rgba(var(--v-border-color),var(--v-border-opacity));text-align:start;white-space:nowrap}
.steps-table td{padding:.4rem .35rem;border-bottom:1px solid rgba(var(--v-border-color),var(--v-border-opacity));vertical-align:middle;overflow:hidden;text-overflow:ellipsis}
.steps-table__row{cursor:pointer}
.steps-table__row:hover{background:rgba(var(--v-theme-on-surface),.03)}
.steps-table__expand td{background:rgba(var(--v-theme-on-surface),.02);padding:.5rem .5rem .6rem}
.expand-act{display:flex;align-items:center;gap:.35rem;width:100%;border:0;border-radius:8px;padding:.35rem .45rem;margin-block:.15rem;background:rgba(var(--v-theme-on-surface),.03);cursor:pointer;text-align:start;color:inherit;font:inherit}
.expand-act:hover{background:rgba(var(--v-theme-primary),.08)}
.step-card{border:1px solid rgba(var(--v-border-color),var(--v-border-opacity));border-radius:10px;padding:.6rem .7rem;cursor:pointer;background:rgb(var(--v-theme-surface))}
.step-card--static{cursor:default}
.hero-step{position:relative;border-radius:12px;overflow:hidden;border:1px solid rgba(var(--v-border-color),var(--v-border-opacity));background:rgb(var(--v-theme-surface))}
.hero-step::before{content:'';position:absolute;inset-block:0;inset-inline-start:0;width:3px;background:rgba(var(--v-theme-on-surface),.18)}
.hero-step--danger::before{background:rgba(var(--v-theme-error),.65)}
.hero-step--warn::before{background:rgba(var(--v-theme-warning),.7)}
.hero-step__body{padding:.55rem .7rem}
.hero-badge{display:inline-flex;padding:.06rem .35rem;border-radius:6px;font-size:.65rem;background:rgba(var(--v-theme-primary),.1);color:rgb(var(--v-theme-primary))}
.hero-title{font-size:.9rem!important;font-weight:500!important;line-height:1.3!important;margin:0}
.hero-meta{display:flex;flex-wrap:wrap;align-items:center;gap:.2rem;font-size:.75rem}
.hero-meta__dot{opacity:.4}
.deadline-pill{display:inline-flex;padding:.06rem .3rem;border-radius:6px;font-size:.65rem;opacity:.85}
.deadline-pill--danger{background:rgba(var(--v-theme-error),.1);color:rgba(var(--v-theme-error),.8)}
.deadline-pill--warn{background:rgba(var(--v-theme-warning),.12);color:rgba(var(--v-theme-on-surface),.65)}
.act-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.insight-card{border:1px solid rgba(var(--v-theme-primary),.16);border-radius:12px!important}
.insight-alert{display:flex;gap:.4rem;align-items:flex-start;padding:.45rem .55rem;border-radius:8px;font-size:.78rem;line-height:1.4}
.insight-alert--danger{background:rgba(var(--v-theme-error),.1);color:rgb(var(--v-theme-error))}
.insight-alert--warn{background:rgba(var(--v-theme-warning),.12);color:rgba(var(--v-theme-on-surface),.8)}
.insight-metrics{display:grid;grid-template-columns:1fr 1fr;gap:.35rem}
.insight-metric{min-width:0;padding:.35rem .45rem;border-radius:8px;background:rgba(var(--v-theme-on-surface),.04)}
.insight-metric__label{display:block;font-size:.65rem;color:rgba(var(--v-theme-on-surface),.5);margin-bottom:.05rem}
.insight-metric__value{display:block;font-size:.78rem;line-height:1.25;overflow-wrap:anywhere}
.form-section-label{font-size:.78rem;font-weight:500;margin-bottom:.15rem}
.empty-soft{font-size:.8rem;color:rgba(var(--v-theme-on-surface),.5);padding:.35rem 0}

/* جلوگیری از اسکرول افقی و اسکرول تو‌در‌تو در این صفحه */
:deep(.v-row){margin:0!important}
:deep(.v-col){padding-block:.35rem!important;padding-inline:.35rem!important}
:deep(.v-card){overflow:visible}
:deep(.v-navigation-drawer .v-navigation-drawer__content){overflow-x:hidden}
:deep(.v-form > .v-input + .v-input),
:deep(.v-form > .v-select + .v-input),
:deep(.v-form > .v-textarea + .v-input){margin-top:.15rem}
:deep(.v-form .v-field){--v-field-padding-top: 6px; --v-field-padding-bottom: 4px}
:deep(.v-form .v-messages){min-height:0}
:deep(.v-form .v-label){font-size:.8rem}
:deep(.v-list-item){min-height:36px!important}
</style>
