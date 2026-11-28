<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  api,
  fetchOpportunity,
  fetchOpportunityStages,
  getUser,
} from '@/utils/api'
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
  const u = getUser<{ id?: number }>()
  return Number(u?.id || 0)
})

const isOwner = computed(() => item.value && currentUserId.value && Number(item.value.owner?.id) === currentUserId.value)
const isReferredAway = computed(() => {
  if (!item.value) return false
  const ownerId = Number(item.value.owner?.id || 0)
  const assigneeId = Number(item.value.assignee?.id || ownerId)
  return Boolean(ownerId && assigneeId && ownerId !== assigneeId)
})
const canMutateStep = computed(() => {
  if (!isViewingOpen.value) return false
  if (isReferredAway.value) {
    if (isOwner.value) return false
    const stepAssignee = Number(currentStep.value?.assignee?.id || item.value?.assignee?.id || 0)
    return stepAssignee > 0 && stepAssignee === currentUserId.value
  }
  return true
})
const canAssign = computed(() => isOwner.value && !isReferredAway.value)
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
const smartInsight = computed(() => {
  if (!item.value) return null
  const actCount = (item.value.activities || []).length
  const bullets: string[] = []
  let tone: 'ok' | 'warn' | 'danger' = 'ok'

  if (currentStep.value?.due_on) {
    const due = new Date(String(currentStep.value.due_on).slice(0, 10))
    const days = Math.ceil((due.getTime() - Date.now()) / 86400000)
    if (days < 0) {
      bullets.push(`مهلت گام «${currentStep.value.title}» گذشته است.`)
      tone = 'danger'
    }
    else if (days <= 3) {
      bullets.push(`تا مهلت گام جاری ${toFaDigits(days)} روز مانده.`)
      tone = 'warn'
    }
  }
  if (isReferredAway.value) {
    bullets.push(`در اختیار «${item.value.assignee?.name || 'کاربر دیگر'}» است تا بازگشت.`)
    if (tone === 'ok') tone = 'warn'
  }
  if (!actCount) {
    bullets.push('هنوز فعالیتی ثبت نشده.')
    if (tone === 'ok') tone = 'warn'
  }
  if (!bullets.length) {
    bullets.push(`گام جاری باز است · ${toFaDigits(actCount)} فعالیت · مرحله ${item.value.stage?.name || '—'}`)
  }
  return {
    tone,
    headline: tone === 'danger' ? 'نیازمند توجه' : tone === 'warn' ? 'حساس' : 'پایدار',
    bullets: bullets.slice(0, 2),
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
function activityBarColor(type: string) {
  return activityVisual(type).color || '#7c3aed'
}

const currentDeadline = computed(() => {
  const due = currentStep.value?.due_on
  if (!due) return { tone: 'ok' as const, label: '', detail: '' }
  const d = new Date(String(due).slice(0, 10))
  const days = Math.ceil((d.getTime() - Date.now()) / 86400000)
  if (Number.isNaN(days)) return { tone: 'ok' as const, label: '', detail: '' }
  if (days < 0) return { tone: 'danger' as const, label: `تأخیر ${toFaDigits(Math.abs(days))} روز`, detail: `تأخیر ${toFaDigits(Math.abs(days))} روز` }
  if (days <= 3) return { tone: 'warn' as const, label: `${toFaDigits(days)} روز`, detail: `${toFaDigits(days)} روز مانده` }
  return { tone: 'ok' as const, label: `${toFaDigits(days)} روز`, detail: `${toFaDigits(days)} روز مانده` }
})

function navRoot(): 'list' | 'detail' {
  const r = String(route.query.root || route.query.from || 'list')
  if (r === 'detail' || r === 'activity') return 'detail'
  return 'list'
}
function goBack() {
  if (navRoot() === 'detail') {
    router.push('/opportunities/' + id.value)
    return
  }
  router.push('/opportunities')
}
function goDashboard() { router.push('/dashboard') }
function goList() { router.push('/opportunities') }
function goActivityCreate() {
  router.push({
    path: '/opportunities/' + id.value + '/activities/new',
    query: { from: 'steps', root: navRoot() },
  })
}

async function load() {
  loading.value = true
  try {
    const [detail, st, us] = await Promise.all([
      fetchOpportunity(id.value),
      fetchOpportunityStages(),
      api<{ data: any[] }>('/api/users').catch(() => ({ data: [] as any[] })),
    ])
    item.value = detail.data
    stages.value = (st.data || []).map((s: any) => ({ title: s.name, value: s.id }))
    users.value = (us.data || []).map((u: any) => ({ title: u.name, value: u.id }))
  }
  catch (e: any) {
    notifyError(e?.message || 'بارگذاری ناموفق بود')
  }
  finally {
    loading.value = false
  }
}

function openClose() {
  closeForm.value = {
    result: '',
    stage_id: item.value?.stage?.id ?? null,
    status: item.value?.status || 'active',
    next_step_title: '',
    next_step_due_on: null,
    assignee_user_id: item.value?.assignee?.id ?? item.value?.owner?.id ?? null,
    return_to_owner: isReferredAway.value && !isOwner.value,
  }
  closeOpen.value = true
}

function openAssign() {
  assignForm.value = {
    to_user_id: null,
    step_title: currentStep.value?.title || item.value?.next_step || '',
    step_due_on: currentStep.value?.due_on || item.value?.next_step_due_on || null,
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
    const res = await api<{ data: any }>(`/api/opportunities/${id.value}/close-step`, {
      method: 'POST',
      body: JSON.stringify({
        result: closeForm.value.result,
        stage_id: closeForm.value.stage_id,
        status: closeForm.value.status,
        next_step_title: closeForm.value.next_step_title || null,
        next_step_due_on: closeForm.value.next_step_due_on,
        assignee_user_id: closeForm.value.assignee_user_id,
        return_to_owner: closeForm.value.return_to_owner,
      }),
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
    const res = await api<{ data: any }>(`/api/opportunities/${id.value}/assign`, {
      method: 'POST',
      body: JSON.stringify({
        to_user_id: assignForm.value.to_user_id,
        step_title: assignForm.value.step_title,
        step_due_on: assignForm.value.step_due_on,
        priority: assignForm.value.priority,
        note: assignForm.value.note || null,
      }),
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
