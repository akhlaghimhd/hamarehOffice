<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import { useOpportunityInsight } from '@/composables/useOpportunityInsight'
import {
  assignOpportunity,
  closeOpportunityStep,
  fetchCompanyUsers,
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
const currentUser = computed(() => getUser<{ id?: number; name?: string; roles?: string[]; permissions?: string[] }>())
const { insight: serverInsight, loadInsight } = useOpportunityInsight(() => id.value)

const item = ref<any | null>(null)
const stages = ref<any[]>([])
const companyUsers = ref<{ title: string; value: number }[]>([])
const loading = ref(false)
const closeOpen = ref(false)
const closing = ref(false)
const assignOpen = ref(false)
const actionSaving = ref(false)
const formRef = ref()
const assignFormRef = ref()
const dirty = ref(false)
const expandedStepIds = ref<number[]>([])
const activityDetail = ref<any | null>(null)
const activityDetailOpen = ref(false)

const closeForm = ref({
  result: '',
  stage_id: null as number | null,
  status: 'active',
  next_step_title: '',
  next_step_due_on: null as string | null,
  assignee_user_id: null as number | null,
  return_to_owner: true,
})
const assignForm = ref({
  to_user_id: null as number | null,
  step_title: '',
  step_due_on: null as string | null,
  priority: 'normal',
  note: '',
})
watch(closeForm, () => { dirty.value = true }, { deep: true })

const statusLabel: Record<string, string> = {
  active: 'فعال', nurturing: 'در حال پرورش', paused: 'متوقف موقت', won: 'بسته‌شده-موفق', lost: 'بسته‌شده-ناموفق',
}
const typeLabel: Record<string, string> = {
  call: 'تماس', meeting: 'جلسه', message: 'ایمیل / پیام', visit: 'بازدید', proposal: 'ارسال پیشنهاد',
  other: 'سایر', note: 'یادداشت', referral: 'ارجاع', step_close: 'بستن گام', assign: 'ارجاع',
}
const priorityLabel: Record<string, string> = { low: 'پایین', normal: 'عادی', high: 'بالا', urgent: 'فوری' }

const isOwner = computed(() => {
  const uid = currentUser.value?.id
  const oid = item.value?.owner?.id
  return uid != null && oid != null && Number(uid) === Number(oid)
})

const currentStep = computed(() => {
  const d = item.value
  if (!d) return null
  if (d.current_step) return d.current_step
  return (d.steps || []).find((s: any) => s.is_current && s.status === 'open')
    || (d.next_step ? { title: d.next_step, due_on: d.next_step_due_on, id: null, status: 'open', assignee: d.assignee } : null)
})

const historySteps = computed(() => {
  const curId = currentStep.value?.id
  return [...(item.value?.steps || [])]
    .filter((s: any) => !s.is_current && s.id !== curId)
    .sort((a: any, b: any) => Number(b.id || 0) - Number(a.id || 0))
})

const isViewingOpen = computed(() => {
  const s = currentStep.value
  if (!s) return false
  if (s.status === 'closed') return false
  return true
})

function activitiesOfStep(stepId: number | null | undefined) {
  if (!item.value?.activities?.length || !stepId) return [] as any[]
  return [...item.value.activities.filter((a: any) => a.step_id === stepId)].sort((a: any, b: any) => {
    const ca = a.created_at ? new Date(a.created_at).getTime() : 0
    const cb = b.created_at ? new Date(b.created_at).getTime() : 0
    if (ca !== cb) return cb - ca
    return Number(b.id || 0) - Number(a.id || 0)
  })
}

const stepActivities = computed(() => activitiesOfStep(currentStep.value?.id))
const stageTone = computed(() => stageVisual(item.value?.stage?.code || item.value?.stage?.name))
const referrals = computed(() => item.value?.referrals || [])

function daysUntil(due: string | null | undefined): number | null {
  if (!due) return null
  const d = new Date(due)
  if (Number.isNaN(d.getTime())) return null
  const today = new Date(); today.setHours(0, 0, 0, 0); d.setHours(0, 0, 0, 0)
  return Math.round((d.getTime() - today.getTime()) / 86400000)
}
function deadlineMeta(due: string | null | undefined) {
  const days = daysUntil(due)
  if (days == null) return { tone: 'neutral', label: 'بدون مهلت', days: null as number | null }
  if (days < 0) return { tone: 'danger', label: `${toFaDigits(Math.abs(days))}روز تأخیر`, days }
  if (days === 0) return { tone: 'warn', label: 'امروز', days }
  if (days <= 2) return { tone: 'warn', label: `${toFaDigits(days)}روز`, days }
  if (days <= 7) return { tone: 'info', label: `${toFaDigits(days)}روز`, days }
  return { tone: 'ok', label: `${toFaDigits(days)}روز`, days }
}
const currentDeadline = computed(() => deadlineMeta(currentStep.value?.due_on))
function stepAssigneeName(step: any) {
  return step?.assignee?.name || item.value?.assignee?.name || item.value?.owner?.name || null
}
function stepAssigneeId(step: any) {
  return step?.assignee?.id || item.value?.assignee?.id || item.value?.owner?.id || null
}
const isMyStep = computed(() => {
  const uid = currentUser.value?.id
  const aid = stepAssigneeId(currentStep.value)
  return uid != null && aid != null && Number(uid) === Number(aid)
})
const assigneeLabel = computed(() => isMyStep.value ? 'خودت' : (stepAssigneeName(currentStep.value) || 'نامشخص'))
const canCloseCurrent = computed(() => isViewingOpen.value && (isOwner.value || isMyStep.value))
const canDefineNextStep = computed(() => isOwner.value)
const canFinalizeOpportunity = computed(() => isOwner.value)

const smartInsight = computed(() => {
  const d = item.value
  if (!d) return null
  const steps = d.steps || []
  const activities = d.activities || []
  const openSteps = steps.filter((s: any) => s.status === 'open' || s.is_current)
  const overdue = openSteps.filter((s: any) => {
    const days = daysUntil(s.due_on)
    return days != null && days < 0
  })
  let alert: string | null = null
  let scoreTone: 'ok' | 'warn' | 'danger' = 'ok'
  let scoreLabel = 'پایدار'
  if (overdue.length) {
    alert = `${toFaDigits(overdue.length)} گام از مهلت گذشته.`
    scoreTone = 'danger'; scoreLabel = 'نیازمند توجه'
  }
  else if (currentDeadline.value.tone === 'warn' || currentDeadline.value.tone === 'danger') {
    alert = `مهلت گام جاری: ${currentDeadline.value.label}`
    scoreTone = currentDeadline.value.tone === 'danger' ? 'danger' : 'warn'
    scoreLabel = scoreTone === 'danger' ? 'نیازمند توجه' : 'حساس'
  }
  else if (!activities.length) {
    alert = 'هنوز فعالیتی ثبت نشده.'
    scoreTone = 'warn'; scoreLabel = 'حساس'
  }
  if (serverInsight.value?.score_tone === 'danger' || serverInsight.value?.score_tone === 'warn') {
    scoreTone = serverInsight.value.score_tone
    scoreLabel = serverInsight.value.score_label || scoreLabel
  }
  if (serverInsight.value?.alert) alert = serverInsight.value.alert
  return {
    scoreLabel, scoreTone, alert,
    focus: currentStep.value ? `گام جاری: ${currentStep.value.title}` : 'گام بازی تعریف نشده',
    metrics: [
      { label: 'وضعیت', value: statusLabel[d.status] || d.status },
      { label: 'مرحله قیف', value: d.stage?.name || '—' },
      { label: 'گام باز', value: toFaDigits(openSteps.length) },
      { label: 'فعالیت', value: toFaDigits(activities.length) },
    ],
    source: 'local' as const,
  }
})

async function ensureUsers() {
  if (companyUsers.value.length) return
  const res = await fetchCompanyUsers()
  companyUsers.value = (res.data || []).map((u: any) => ({ title: u.name, value: u.id }))
}

async function load() {
  if (!getCompany()?.id) { notifyError('شرکت جاری مشخص نشده است.'); return }
  loading.value = true
  try {
    const [res, stageRes] = await Promise.all([fetchOpportunity(id.value), fetchOpportunityStages()])
    item.value = res.data
    stages.value = (stageRes.data || []).map((s: any) => ({ title: s.name, value: s.id, code: s.code }))
    closeForm.value.stage_id = res.data?.stage?.id ?? null
    closeForm.value.status = res.data?.status || 'active'
    dirty.value = false
    expandedStepIds.value = []
    void loadInsight()
  }
  catch (e: any) { notifyError(e?.message || 'خطا در بارگذاری') }
  finally { loading.value = false }
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
  if (!isOwner.value && !isMyStep.value) {
    notifyError('فقط زیر گام ارجاع‌شده به خودتان می‌توانید فعالیت ثبت کنید.')
    return
  }
  router.push(`/opportunities/${id.value}/activities/new?from=steps`)
}
function toggleStepExpand(stepId: number, e?: Event) {
  e?.stopPropagation()
  if (expandedStepIds.value.includes(stepId))
    expandedStepIds.value = expandedStepIds.value.filter(x => x !== stepId)
  else
    expandedStepIds.value = [...expandedStepIds.value, stepId]
}
function isExpanded(stepId: number) { return expandedStepIds.value.includes(stepId) }
function openActivityDetail(a: any) {
  activityDetail.value = a
  activityDetailOpen.value = true
}
function openClose() {
  if (!canCloseCurrent.value) { notifyError('اجازه بستن این گام را ندارید.'); return }
  closeForm.value = {
    result: '', next_step_title: '', next_step_due_on: null,
    stage_id: item.value?.stage?.id ?? null,
    status: item.value?.status || 'active',
    assignee_user_id: item.value?.owner?.id ?? currentUser.value?.id ?? null,
    return_to_owner: !isOwner.value,
  }
  dirty.value = false
  void ensureUsers().catch(() => {})
  closeOpen.value = true
}
async function submitClose() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid) return
  closing.value = true
  try {
    const payload: Record<string, unknown> = {
      result: closeForm.value.result,
      stage_id: closeForm.value.stage_id,
      status: closeForm.value.status,
      return_to_owner: closeForm.value.return_to_owner,
    }
    if (canDefineNextStep.value && closeForm.value.next_step_title) {
      payload.next_step_title = closeForm.value.next_step_title
      payload.next_step_due_on = closeForm.value.next_step_due_on
      payload.assignee_user_id = closeForm.value.assignee_user_id
    }
    const res = await closeOpportunityStep(id.value, payload)
    item.value = res.data
    dirty.value = false
    closeOpen.value = false
    success('گام بسته شد')
    void loadInsight()
  }
  catch (e: any) { notifyError(e?.message || 'بستن گام ناموفق') }
  finally { closing.value = false }
}
async function openAssign() {
  assignForm.value = {
    to_user_id: null,
    step_title: currentStep.value?.title || '',
    step_due_on: currentStep.value?.due_on || null,
    priority: 'normal',
    note: '',
  }
  try { await ensureUsers() }
  catch (e: any) { notifyError(e?.message || 'خطا در دریافت کاربران'); return }
  assignOpen.value = true
}
async function submitAssign() {
  const { valid } = await assignFormRef.value?.validate?.() ?? { valid: true }
  if (!valid) return
  actionSaving.value = true
  try {
    const res = await assignOpportunity(id.value, {
      to_user_id: assignForm.value.to_user_id,
      step_title: assignForm.value.step_title,
      step_due_on: assignForm.value.step_due_on,
      priority: assignForm.value.priority,
      note: assignForm.value.note || null,
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
      <div class="page-breadcrumb d-flex flex-wrap align-center gap-1 text-caption text-medium-emphasis mb-2">
        <button type="button" class="crumb-btn" @click="goDashboard">خانه</button>
        <VIcon icon="ri-arrow-left-s-line" size="14" />
        <button type="button" class="crumb-btn" @click="goList">فرصت‌ها</button>
        <VIcon icon="ri-arrow-left-s-line" size="14" />
        <span class="text-truncate">{{ item.organization_name }}</span>
      </div>

      <div class="page-head d-flex flex-wrap align-center gap-2 mb-3">
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
            class="hero-step mb-3"
            :class="[`hero-step--${currentDeadline.tone}`, { 'hero-step--closed': !isViewingOpen }]"
          >
            <div class="hero-step__body">
              <div class="d-flex flex-wrap align-center justify-space-between gap-2">
                <div class="min-w-0 flex-grow-1">
                  <div class="d-flex flex-wrap align-center gap-2 mb-1">
                    <span class="hero-badge">گام جاری</span>
                    <span
                      v-if="isViewingOpen && currentDeadline.tone !== 'ok' && currentDeadline.tone !== 'neutral'"
                      class="deadline-pill"
                      :class="`deadline-pill--${currentDeadline.tone}`"
                    >{{ currentDeadline.label }}</span>
                  </div>
                  <h2 class="hero-title text-wrap mb-1">{{ currentStep.title }}</h2>
                  <div class="hero-meta text-caption text-medium-emphasis">
                    <span>{{ formatJalaliFa(currentStep.due_on) || 'بدون مهلت' }}</span>
                    <span class="hero-meta__dot">·</span>
                    <span :class="{ 'text-primary': isMyStep }">{{ assigneeLabel }}</span>
                  </div>
                </div>
                <VBtn
                  v-if="canCloseCurrent"
                  color="primary"
                  size="small"
                  variant="flat"
                  prepend-icon="ri-checkbox-circle-line"
                  @click="openClose"
                >بستن گام</VBtn>
              </div>
            </div>
          </div>

          <VCard class="mb-3 main-card">
            <VCardText class="pa-3">
              <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
                <div class="text-subtitle-2 font-weight-medium">فعالیت‌های گام جاری</div>
                <VBtn
                  v-if="isViewingOpen && (isOwner || isMyStep)"
                  color="primary"
                  size="small"
                  variant="tonal"
                  prepend-icon="ri-add-line"
                  @click="goActivityNew"
                >ثبت فعالیت</VBtn>
              </div>
              <div v-if="!stepActivities.length" class="empty-soft">هنوز فعالیتی ثبت نشده.</div>
              <VExpansionPanels v-else variant="accordion">
                <VExpansionPanel v-for="a in stepActivities" :key="a.id">
                  <VExpansionPanelTitle class="py-2">
                    <div class="d-flex align-center gap-2 min-w-0 flex-grow-1">
                      <span class="act-dot" :style="{ background: activityBarColor(a.type) }" />
                      <VIcon :icon="activityVisual(a.type).icon" size="16" />
                      <span class="text-body-2 text-truncate">{{ typeLabel[a.type] || a.subject || a.type }}</span>
                      <span class="text-caption text-medium-emphasis ms-auto flex-shrink-0">{{ formatJalaliFa(a.happened_at || a.created_at) }}</span>
                    </div>
                  </VExpansionPanelTitle>
                  <VExpansionPanelText>
                    <div class="text-body-2 text-wrap mb-2">{{ a.body || '—' }}</div>
                    <VBtn size="x-small" variant="text" color="primary" @click="openActivityDetail(a)">مشاهده کامل</VBtn>
                  </VExpansionPanelText>
                </VExpansionPanel>
              </VExpansionPanels>
            </VCardText>
          </VCard>

          <VCard class="mb-3 main-card">
            <VCardText class="pa-3">
              <div class="text-subtitle-2 font-weight-medium mb-3">گام‌های قبلی</div>
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
                          <VBtn icon size="x-small" variant="text" @click="toggleStepExpand(s.id, $event)">
                            <VIcon :icon="isExpanded(s.id) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" size="18" />
                          </VBtn>
                        </td>
                        <td>
                          <div class="d-flex align-center gap-1">
                            <VIcon icon="ri-checkbox-circle-line" size="16" color="success" />
                            <span class="text-body-2">{{ s.title }}</span>
                          </div>
                        </td>
                        <td><VChip size="x-small" color="secondary" variant="tonal">{{ s.status === 'closed' ? 'بسته' : 'آرشیو' }}</VChip></td>
                        <td class="text-caption">{{ s.status === 'closed' ? (formatJalaliFa(s.closed_at) || '—') : '—' }}</td>
                        <td class="text-caption">{{ toFaDigits(activitiesOfStep(s.id).length) }}</td>
                      </tr>
                      <tr v-if="isExpanded(s.id)" class="steps-table__expand">
                        <td colspan="5">
                          <div v-if="s.result" class="text-caption text-medium-emphasis mb-2">نتیجه: {{ s.result }}</div>
                          <div v-if="!activitiesOfStep(s.id).length" class="text-caption text-medium-emphasis pa-2">بدون فعالیت</div>
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
                        بسته: {{ formatJalaliFa(s.closed_at) || '—' }} · {{ toFaDigits(activitiesOfStep(s.id).length) }} فعالیت
                      </div>
                    </div>
                    <VBtn icon size="x-small" variant="text" @click="toggleStepExpand(s.id, $event)">
                      <VIcon :icon="isExpanded(s.id) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" size="18" />
                    </VBtn>
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

          <VCard v-if="referrals.length" class="mb-3 main-card">
            <VCardText class="pa-3">
              <div class="text-subtitle-2 font-weight-medium mb-3">گردش و ارجاع فرصت</div>
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
          <VCard v-if="smartInsight" class="mb-3 insight-card">
            <VCardText class="pa-3">
              <div class="d-flex align-center justify-space-between gap-2 mb-3">
                <div class="text-body-2 d-flex align-center gap-1 text-medium-emphasis">
                  <VIcon icon="ri-pulse-line" size="18" color="primary" />وضعیت فرصت
                </div>
                <VChip size="small" variant="flat" :color="smartInsight.scoreTone === 'danger' ? 'error' : smartInsight.scoreTone === 'warn' ? 'warning' : 'success'">
                  {{ smartInsight.scoreLabel }}
                </VChip>
              </div>
              <div v-if="smartInsight.alert" class="insight-alert mb-3" :class="`insight-alert--${smartInsight.scoreTone}`">
                <VIcon :icon="smartInsight.scoreTone === 'ok' ? 'ri-information-line' : 'ri-error-warning-line'" size="18" class="flex-shrink-0 mt-1" />
                <span>{{ smartInsight.alert }}</span>
              </div>
              <div class="insight-metrics mb-2">
                <div v-for="(m, i) in smartInsight.metrics" :key="i" class="insight-metric">
                  <span class="insight-metric__label">{{ m.label }}</span>
                  <span class="insight-metric__value">{{ m.value }}</span>
                </div>
              </div>
              <div class="text-caption text-medium-emphasis">{{ smartInsight.focus }}</div>
            </VCardText>
          </VCard>

          <VCard class="mb-3 main-card">
            <VCardText class="pa-3">
              <div class="text-subtitle-2 font-weight-medium mb-3">خلاصه فرصت</div>
              <div class="text-body-2 mb-2"><span class="text-medium-emphasis">طرف:</span> {{ item.organization_name }}</div>
              <div class="text-body-2 mb-2"><span class="text-medium-emphasis">مالک:</span> {{ item.owner?.name || '—' }}</div>
              <div class="text-body-2 mb-2"><span class="text-medium-emphasis">مسئول فعلی:</span> {{ item.assignee?.name || '—' }}</div>
              <div class="text-body-2 mb-2"><span class="text-medium-emphasis">وضعیت:</span> {{ statusLabel[item.status] || item.status }}</div>
              <div class="text-body-2 mb-3"><span class="text-medium-emphasis">مرحله قیف:</span> {{ item.stage?.name || '—' }}</div>
              <VBtn block size="small" variant="tonal" color="secondary" prepend-icon="ri-user-add-line" @click="openAssign">ارجاع فرصت</VBtn>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <FormDrawer v-model="closeOpen" title="بستن گام جاری" :subtitle="currentStep?.title || ''" :dirty="dirty" :width="480">
        <VForm ref="formRef" class="close-form" @submit.prevent="submitClose">
          <VTextarea v-model="closeForm.result" label="نتیجه این گام *" :rules="[rules.required]" rows="3" class="mb-4" hint="چه کاری انجام شد و خروجی چه بود؟" persistent-hint />
          <div class="form-section-label mb-2">مرحله فروش در قیف</div>
          <div class="text-caption text-medium-emphasis mb-2">مثلاً سرنخ، پیشنهاد، مذاکره — مسیر فروش در قیف را نشان می‌دهد.</div>
          <VSelect v-model="closeForm.stage_id" :items="stages" label="مرحله قیف" clearable class="mb-4" />
          <template v-if="canFinalizeOpportunity">
            <div class="form-section-label mb-2">وضعیت کلی فرصت</div>
            <div class="text-caption text-medium-emphasis mb-2">آیا فرصت هنوز فعال است یا نهایی شده؟ فقط مالک می‌تواند موفق/ناموفق ببندد.</div>
            <VSelect v-model="closeForm.status" :items="Object.entries(statusLabel).map(([value, title]) => ({ title, value }))" label="وضعیت فرصت" class="mb-4" />
          </template>
          <template v-if="canDefineNextStep">
            <VDivider class="mb-4" />
            <div class="form-section-label mb-2">گام بعدی (اختیاری — فقط مالک)</div>
            <VTextField v-model="closeForm.next_step_title" label="عنوان گام بعدی" class="mb-4" placeholder="مثلاً: ارسال پیش‌فاکتور" />
            <JalaliDateField v-model="closeForm.next_step_due_on" label="مهلت گام بعدی" class="mb-4" />
            <VSelect v-model="closeForm.assignee_user_id" :items="companyUsers" label="مسئول گام بعدی" clearable class="mb-2" />
          </template>
          <VAlert v-else type="info" variant="tonal" density="compact" class="mb-2">
            با بستن این ماموریت، فرصت به مالک برمی‌گردد تا گام بعدی را تعریف کند یا ارجاع دهد.
          </VAlert>
        </VForm>
        <template #actions>
          <VBtn variant="text" color="secondary" @click="closeOpen = false">انصراف</VBtn>
          <VBtn color="primary" :loading="closing" @click="submitClose">ثبت و بستن گام</VBtn>
        </template>
      </FormDrawer>

      <FormDrawer v-model="assignOpen" title="ارجاع فرصت" :subtitle="item.organization_name" :width="480">
        <VForm ref="assignFormRef" @submit.prevent="submitAssign">
          <div class="text-caption text-medium-emphasis mb-3">
            ماموریت به‌صورت یک گام جاری برای کاربر مقصد ثبت می‌شود. او فقط زیر همان گام فعالیت ثبت می‌کند؛ تعریف گام بعدی و بستن نهایی فرصت با مالک است.
          </div>
          <VSelect v-model="assignForm.to_user_id" :items="companyUsers" label="ارجاع به *" class="mb-4" :rules="[rules.required]" />
          <VTextField v-model="assignForm.step_title" label="عنوان ماموریت / گام *" class="mb-4" :rules="[rules.required]" hint="همین عنوان گام جاری مقصد می‌شود" persistent-hint />
          <JalaliDateField v-model="assignForm.step_due_on" label="مهلت ماموریت" class="mb-4" />
          <VSelect v-model="assignForm.priority" :items="[{ title: 'عادی', value: 'normal' }, { title: 'بالا', value: 'high' }, { title: 'فوری', value: 'urgent' }, { title: 'پایین', value: 'low' }]" label="اولویت" class="mb-4" />
          <VTextarea v-model="assignForm.note" label="توضیح برای مقصد" rows="3" class="mb-2" />
        </VForm>
        <template #actions>
          <VBtn variant="text" color="secondary" @click="assignOpen = false">انصراف</VBtn>
          <VBtn color="primary" :loading="actionSaving" @click="submitAssign">ثبت ارجاع</VBtn>
        </template>
      </FormDrawer>

      <VDialog v-model="activityDetailOpen" max-width="520" scrollable>
        <VCard v-if="activityDetail" rounded="lg">
          <VCardTitle class="d-flex align-center gap-2 text-wrap">
            <VIcon :icon="activityVisual(activityDetail.type).icon" size="20" />
            {{ typeLabel[activityDetail.type] || activityDetail.subject || activityDetail.type }}
          </VCardTitle>
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
  </div>
</template>

<style scoped>
.steps-workspace{max-width:1200px;width:100%;overflow-x:hidden}
.page-title{font-size:1.05rem!important}
.crumb-btn{background:none;border:0;padding:0;color:inherit;cursor:pointer}
.crumb-btn:hover{color:rgb(var(--v-theme-primary))}
.main-card{border-radius:12px!important}
.empty-soft{padding:.85rem;border-radius:10px;background:rgba(var(--v-theme-on-surface),.03);color:rgba(var(--v-theme-on-surface),.55);font-size:.85rem}
.table-wrap{width:100%;overflow-x:auto}
.steps-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:.875rem}
.steps-table th{text-align:start;padding:.5rem .4rem;color:rgba(var(--v-theme-on-surface),.55);font-weight:500;font-size:.75rem;border-bottom:1px solid rgba(var(--v-border-color),var(--v-border-opacity))}
.steps-table td{padding:.45rem .4rem;border-bottom:1px solid rgba(var(--v-border-color),var(--v-border-opacity));vertical-align:middle;overflow:hidden;text-overflow:ellipsis}
.steps-table__row{cursor:pointer}
.steps-table__row:hover{background:rgba(var(--v-theme-on-surface),.03)}
.steps-table__expand td{background:rgba(var(--v-theme-on-surface),.02);padding:.35rem .5rem .6rem 2rem}
.expand-act{display:flex;align-items:center;gap:.4rem;width:100%;padding:.4rem .5rem;margin-bottom:.25rem;border:0;border-radius:8px;background:rgba(var(--v-theme-on-surface),.03);cursor:pointer;text-align:start;color:inherit;font:inherit}
.expand-act:hover{background:rgba(var(--v-theme-primary),.08)}
.step-card{border:1px solid rgba(var(--v-border-color),var(--v-border-opacity));border-radius:10px;padding:.75rem;cursor:pointer;background:rgb(var(--v-theme-surface))}
.step-card--static{cursor:default}
.hero-step{position:relative;border-radius:12px;overflow:hidden;border:1px solid rgba(var(--v-border-color),var(--v-border-opacity));background:rgb(var(--v-theme-surface))}
.hero-step::before{content:'';position:absolute;inset-block:0;inset-inline-start:0;width:3px;background:rgba(var(--v-theme-on-surface),.18)}
.hero-step--danger::before{background:rgba(var(--v-theme-error),.65)}.hero-step--warn::before{background:rgba(var(--v-theme-warning),.7)}
.hero-step__body{padding:.7rem .85rem}
.hero-badge{display:inline-flex;padding:.08rem .4rem;border-radius:6px;font-size:.68rem;background:rgba(var(--v-theme-primary),.1);color:rgb(var(--v-theme-primary))}
.hero-title{font-size:.95rem!important;font-weight:500!important;line-height:1.35!important;margin:0}
.hero-meta{display:flex;flex-wrap:wrap;align-items:center;gap:.25rem}.hero-meta__dot{opacity:.4}
.deadline-pill{display:inline-flex;padding:.08rem .35rem;border-radius:6px;font-size:.68rem;opacity:.85}
.deadline-pill--danger{background:rgba(var(--v-theme-error),.1);color:rgba(var(--v-theme-error),.8)}
.deadline-pill--warn{background:rgba(var(--v-theme-warning),.12);color:rgba(var(--v-theme-on-surface),.65)}
.act-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.insight-card{border:1px solid rgba(var(--v-theme-primary),.16);border-radius:12px!important}
.insight-alert{display:flex;gap:.45rem;align-items:flex-start;padding:.55rem .65rem;border-radius:8px;font-size:.8rem;line-height:1.45}
.insight-alert--danger{background:rgba(var(--v-theme-error),.1);color:rgb(var(--v-theme-error))}
.insight-alert--warn{background:rgba(var(--v-theme-warning),.12);color:rgba(var(--v-theme-on-surface),.8)}
.insight-metrics{display:grid;grid-template-columns:1fr 1fr;gap:.4rem}
.insight-metric{min-width:0;padding:.4rem .5rem;border-radius:8px;background:rgba(var(--v-theme-on-surface),.04)}
.insight-metric__label{display:block;font-size:.68rem;color:rgba(var(--v-theme-on-surface),.5);margin-bottom:.1rem}
.insight-metric__value{display:block;font-size:.8rem;line-height:1.3;overflow-wrap:anywhere}
.form-section-label{font-size:.8rem;font-weight:500}
</style>
