<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  createOpportunityActivity,
  fetchOpportunity,
  getCompany,
} from '@/utils/api'
import { formatJalaliFa, toFaDigits, todayIso } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotify()

const opportunityId = computed(() => Number(route.params.id))
const fromSource = computed(() => String(route.query.from || ''))

const loading = ref(true)
const saving = ref(false)
const detail = ref<any | null>(null)
const formRef = ref()
const suppressValidate = ref(false)

const activityTypes = [
  { title: 'تماس تلفنی', value: 'call' },
  { title: 'جلسه', value: 'meeting' },
  { title: 'ایمیل / پیام', value: 'message' },
  { title: 'بازدید', value: 'visit' },
  { title: 'ارسال پیشنهاد', value: 'proposal' },
  { title: 'سایر', value: 'other' },
]

const typeLabel: Record<string, string> = Object.fromEntries(
  activityTypes.map(t => [t.value, t.title]),
)

const statusLabel: Record<string, string> = {
  active: 'فعال',
  nurturing: 'در حال پرورش',
  paused: 'متوقف موقت',
  won: 'بسته‌شده-موفق',
  lost: 'بسته‌شده-ناموفق',
}

const form = ref({
  type: '' as string,
  happened_on: todayIso() as string | null,
  body: '',
  result: '',
})

type LocalAttachment = {
  id: string
  file: File
  kind: 'image' | 'pdf' | 'audio' | 'other'
  url: string
  name: string
}

const attachments = ref<LocalAttachment[]>([])
const fileInput = ref<HTMLInputElement | null>(null)
const previewOpen = ref(false)
const previewItem = ref<LocalAttachment | null>(null)

const MAX_FILES = 5
const MAX_AUDIO_SEC = 180
const isRecording = ref(false)
const recordSeconds = ref(0)
let mediaRecorder: MediaRecorder | null = null
let recordTimer: ReturnType<typeof setInterval> | null = null
let audioChunks: BlobPart[] = []

const historyDetailOpen = ref(false)
const selectedHistory = ref<any | null>(null)

const currentStep = computed(() => {
  const d = detail.value
  if (!d) return null
  if (d.current_step) return d.current_step
  const open = (d.steps || []).find((s: any) => s.is_current && s.status === 'open')
  if (open) return open
  return d.next_step ? { title: d.next_step, due_on: d.next_step_due_on, id: null } : null
})

const stepActivities = computed(() => {
  const d = detail.value
  if (!d?.activities?.length) return [] as any[]
  const stepId = currentStep.value?.id
  const list = stepId
    ? d.activities.filter((a: any) => a.step_id === stepId || !a.step_id)
    : [...d.activities]
  return [...list].sort((a: any, b: any) => {
    const ca = a.created_at ? new Date(a.created_at).getTime() : 0
    const cb = b.created_at ? new Date(b.created_at).getTime() : 0
    if (ca !== cb) return cb - ca
    return Number(b.id || 0) - Number(a.id || 0)
  })
})

function attachmentsOf(a: any): any[] {
  if (Array.isArray(a?.attachments) && a.attachments.length) return a.attachments
  return []
}

async function load() {
  if (!getCompany()?.id) {
    notifyError('شرکت جاری مشخص نشده است.')
    return
  }
  loading.value = true
  try {
    const res = await fetchOpportunity(opportunityId.value)
    detail.value = res.data
  }
  catch (e: any) {
    notifyError(e?.message || 'خطا در بارگذاری فرصت')
  }
  finally {
    loading.value = false
  }
}

/** ریشه ورود از query.root یا from — بدون history.back تا حلقه بین فرزندها ساخته نشود */
function navRoot(): 'list' | 'detail' {
  const r = String(route.query.root || '')
  if (r === 'detail') return 'detail'
  if (r === 'list') return 'list'
  const from = fromSource.value
  if (from === 'detail') return 'detail'
  if (from === 'list') return 'list'
  // از steps آمده ولی root مشخص نیست → فهرست امن‌تر از گیر کردن در حلقه
  return 'list'
}

function goBack() {
  // بازگشت فقط به ریشه والد (فهرست فرصت‌ها یا صفحه فرصت) — نه دوباره به مدیریت گام
  if (navRoot() === 'detail') {
    router.push('/opportunities/' + opportunityId.value)
    return
  }
  router.push('/opportunities')
}

function goDashboard() { router.push('/dashboard') }
function goList() { router.push('/opportunities') }
function goOpportunity() { router.push('/opportunities/' + opportunityId.value) }
function goStepManage() {
  router.push('/opportunities/' + opportunityId.value + '/steps?root=' + navRoot())
}

function detectKind(file: File): LocalAttachment['kind'] {
  if (file.type.startsWith('image/')) return 'image'
  if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) return 'pdf'
  if (file.type.startsWith('audio/')) return 'audio'
  return 'other'
}

function clearAttachments() {
  for (const a of attachments.value) URL.revokeObjectURL(a.url)
  attachments.value = []
}

function addFiles(files: File[]) {
  if (!files.length) return
  const next = [...attachments.value]
  let skipped = 0
  for (const file of files) {
    if (next.length >= MAX_FILES) { skipped += 1; continue }
    const dup = next.some(a => a.name === file.name && a.file.size === file.size)
    if (dup) { skipped += 1; continue }
    const kind = detectKind(file)
    next.push({
      id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
      file,
      kind,
      url: URL.createObjectURL(file),
      name: file.name,
    })
  }
  attachments.value = next
  if (skipped) notifyError(`حداکثر ${toFaDigits(MAX_FILES)} فایل. بعضی فایل‌ها اضافه نشدند.`)
}

function onFilesChange(e: Event) {
  const input = e.target as HTMLInputElement
  addFiles(Array.from(input.files || []))
  input.value = ''
}

function removeAttachment(id: string) {
  const item = attachments.value.find(a => a.id === id)
  if (item) URL.revokeObjectURL(item.url)
  attachments.value = attachments.value.filter(a => a.id !== id)
}

function openPreview(item: LocalAttachment) {
  previewItem.value = item
  previewOpen.value = true
}

function downloadAttachment(item: LocalAttachment) {
  const a = document.createElement('a')
  a.href = item.url
  a.download = item.name
  a.click()
}

async function toggleRecord() {
  if (isRecording.value) { stopRecording(true); return }
  if (attachments.value.length >= MAX_FILES) { notifyError(`حداکثر ${toFaDigits(MAX_FILES)} فایل.`); return }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    audioChunks = []
    mediaRecorder = new MediaRecorder(stream)
    mediaRecorder.ondataavailable = (ev) => { if (ev.data.size > 0) audioChunks.push(ev.data) }
    mediaRecorder.onstop = () => {
      stream.getTracks().forEach(t => t.stop())
      if (!audioChunks.length) return
      const blob = new Blob(audioChunks, { type: mediaRecorder?.mimeType || 'audio/webm' })
      const file = new File([blob], `voice-${Date.now()}.webm`, { type: blob.type })
      addFiles([file])
      audioChunks = []
    }
    mediaRecorder.start()
    isRecording.value = true
    recordSeconds.value = 0
    recordTimer = setInterval(() => {
      recordSeconds.value += 1
      if (recordSeconds.value >= MAX_AUDIO_SEC) stopRecording(true)
    }, 1000)
  }
  catch {
    notifyError('دسترسی به میکروفون ممکن نشد.')
  }
}

function stopRecording(save: boolean) {
  if (recordTimer) { clearInterval(recordTimer); recordTimer = null }
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    if (!save) mediaRecorder.ondataavailable = null
    mediaRecorder.stop()
  }
  mediaRecorder = null
  isRecording.value = false
}

function formatRecordTime(sec: number) {
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${toFaDigits(m)}:${toFaDigits(String(s).padStart(2, '0'))}`
}

function openHistory(a: any) {
  selectedHistory.value = a
  historyDetailOpen.value = true
}

function splitBody(body: string) {
  if (!body) return { summary: '', result: '' }
  const marker = 'نتیجه/واکنش:'
  const idx = body.indexOf(marker)
  if (idx === -1) return { summary: body, result: '' }
  return { summary: body.slice(0, idx).trim(), result: body.slice(idx + marker.length).trim() }
}

async function resetFormClean() {
  suppressValidate.value = true
  form.value = { type: '', happened_on: todayIso(), body: '', result: '' }
  clearAttachments()
  await nextTick()
  formRef.value?.resetValidation?.()
  await nextTick()
  formRef.value?.resetValidation?.()
  suppressValidate.value = false
}

async function submit() {
  if (saving.value || suppressValidate.value) return
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid) return
  const typeTitle = activityTypes.find(t => t.value === form.value.type)?.title || form.value.type
  saving.value = true
  try {
    await createOpportunityActivity(opportunityId.value, {
      type: form.value.type || 'note',
      subject: typeTitle,
      body: form.value.body,
      result: form.value.result || null,
      happened_at: form.value.happened_on || null,
      step_id: currentStep.value?.id ?? null,
    })
    success('فعالیت ثبت شد')
    goBack()
  }
  catch (e: any) {
    notifyError(e?.message || 'ثبت فعالیت ناموفق بود')
  }
  finally {
    saving.value = false
  }
}

onMounted(load)
onBeforeUnmount(() => {
  stopRecording(false)
  clearAttachments()
})
</script>

<template>
  <div class="activity-page">
    <div class="page-breadcrumb d-flex flex-wrap align-center gap-1 text-caption text-medium-emphasis mb-2">
      <button type="button" class="crumb-btn" @click="goDashboard">خانه</button>
      <VIcon icon="ri-arrow-left-s-line" size="14" />
      <button type="button" class="crumb-btn" @click="goList">فرصت‌ها</button>
      <template v-if="detail?.organization_name">
        <VIcon icon="ri-arrow-left-s-line" size="14" />
        <span class="text-truncate">{{ detail.organization_name }}</span>
      </template>
    </div>

    <div class="page-head d-flex flex-wrap align-center gap-2 mb-3">
      <VBtn icon variant="text" color="secondary" size="small" density="comfortable" @click="goBack">
        <VIcon icon="ri-arrow-right-s-line" size="26" />
      </VBtn>
      <h1 class="page-title text-h6 font-weight-medium mb-0">
        <template v-if="detail?.organization_name">ثبت فعالیت برای «{{ detail.organization_name }}»</template>
        <template v-else>ثبت فعالیت</template>
      </h1>
    </div>

    <div v-if="loading" class="text-center py-8 text-medium-emphasis">در حال بارگذاری...</div>

    <template v-else-if="detail">
      <VRow dense>
        <VCol cols="12" lg="8">
          <VCard class="pa-3 pa-md-4">
            <div class="text-subtitle-1 font-weight-medium mb-3">چه کاری انجام شد؟</div>
            <VForm ref="formRef" @submit.prevent="submit">
              <VRow dense>
                <VCol cols="12" sm="6">
                  <VSelect v-model="form.type" :items="activityTypes" label="نوع *" density="compact" hide-details="auto" :rules="suppressValidate ? [] : [rules.required('نوع')]" />
                </VCol>
                <VCol cols="12" sm="6">
                  <JalaliDateField v-model="form.happened_on" label="تاریخ *" density="compact" :rules="suppressValidate ? [] : [rules.required('تاریخ')]" />
                </VCol>
                <VCol cols="12">
                  <VTextarea v-model="form.body" label="خلاصه عملکرد *" rows="2" density="compact" hide-details="auto" :rules="suppressValidate ? [] : [rules.required('خلاصه عملکرد')]" />
                </VCol>
                <VCol cols="12">
                  <VTextarea v-model="form.result" label="نتیجه / واکنش (اختیاری)" rows="2" density="compact" hide-details="auto" />
                </VCol>
              </VRow>
              <div class="d-flex flex-wrap justify-end gap-2 mt-3">
                <VBtn variant="outlined" color="secondary" size="small" @click="goBack">انصراف</VBtn>
                <VBtn color="primary" size="small" type="button" :loading="saving" :disabled="saving" @click="submit">ثبت فعالیت</VBtn>
              </div>
            </VForm>
          </VCard>
        </VCol>
        <VCol cols="12" lg="4">
          <VCard class="pa-3 mb-3">
            <button type="button" class="text-subtitle-1 font-weight-medium mb-2" style="background:none;border:0;padding:0;cursor:pointer;color:inherit" @click="goOpportunity">
              {{ detail.organization_name }}
            </button>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <VChip size="x-small" color="primary" variant="tonal">{{ toFaDigits(detail.code) }}</VChip>
              <VChip size="x-small" color="success" variant="tonal">{{ statusLabel[detail.status] || detail.status }}</VChip>
            </div>
            <div class="text-caption text-medium-emphasis mb-1">گام جاری</div>
            <button v-if="currentStep" type="button" class="text-body-2" style="background:none;border:0;padding:0;cursor:pointer;color:inherit" @click="goStepManage">
              {{ currentStep.title }}
            </button>
            <div v-else class="text-caption text-medium-emphasis">گام بازی تعریف نشده</div>
          </VCard>
          <VCard class="pa-3">
            <div class="text-body-2 font-weight-medium mb-2">فعالیت‌های قبلی این گام</div>
            <div v-if="!stepActivities.length" class="text-caption text-medium-emphasis">هنوز فعالیتی ثبت نشده.</div>
            <button
              v-for="a in stepActivities"
              :key="a.id"
              type="button"
              class="d-block w-100 text-start mb-2 pa-2"
              style="background:rgba(0,0,0,.03);border:0;border-radius:8px;cursor:pointer;color:inherit"
              @click="openHistory(a)"
            >
              <div class="text-body-2">{{ typeLabel[a.type] || a.subject || a.type }}</div>
              <div class="text-caption text-medium-emphasis">{{ formatJalaliFa(a.happened_at || a.created_at) }}</div>
            </button>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <VDialog v-model="historyDetailOpen" max-width="520">
      <VCard v-if="selectedHistory">
        <VCardTitle>{{ typeLabel[selectedHistory.type] || selectedHistory.subject || selectedHistory.type }}</VCardTitle>
        <VCardText>
          <div class="text-caption text-medium-emphasis mb-2">{{ formatJalaliFa(selectedHistory.happened_at || selectedHistory.created_at) }}</div>
          <div class="text-body-2" style="white-space:pre-wrap">{{ selectedHistory.body || '—' }}</div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="historyDetailOpen = false">بستن</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.activity-page { max-width: 1100px; }
.page-title { font-size: 1.05rem !important; }
.crumb-btn { background: none; border: 0; padding: 0; color: inherit; cursor: pointer; }
.crumb-btn:hover { color: rgb(var(--v-theme-primary)); }
</style>
