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

function navRoot(): 'list' | 'detail' {
  const r = String(route.query.root || route.query.from || 'list')
  if (r === 'detail' || r === 'activity') return 'detail'
  return 'list'
}

function goBack() {
  if (navRoot() === 'detail') {
    router.push({ name: 'opportunity-detail', params: { id: id.value } })
    return
  }
  router.push({ name: 'opportunities' })
}
function goDashboard() { router.push({ name: 'dashboard' }) }
function goList() { router.push({ name: 'opportunities' }) }
function goActivityCreate() {
  router.push({
    name: 'opportunity-activity-create',
    params: { id: id.value },
    query: { from: 'steps', root: navRoot() },
  })
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
