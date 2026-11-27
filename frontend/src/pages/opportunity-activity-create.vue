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
  if (!d)
    return null
  if (d.current_step)
    return d.current_step
  const open = (d.steps || []).find((s: any) => s.is_current && s.status === 'open')
  if (open)
    return open
  return d.next_step
    ? { title: d.next_step, due_on: d.next_step_due_on, id: null }
    : null
})

const stepActivities = computed(() => {
  const d = detail.value
  if (!d?.activities?.length)
    return [] as any[]

  const stepId = currentStep.value?.id
  const list = stepId
    ? d.activities.filter((a: any) => a.step_id === stepId || !a.step_id)
    : [...d.activities]

  return [...list].sort((a: any, b: any) => {
    const ca = a.created_at ? new Date(a.created_at).getTime() : 0
    const cb = b.created_at ? new Date(b.created_at).getTime() : 0
    if (ca !== cb)
      return cb - ca
    return Number(b.id || 0) - Number(a.id || 0)
  })
})

function attachmentsOf(a: any): any[] {
  if (Array.isArray(a?.attachments) && a.attachments.length)
    return a.attachments
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

function goBack() {
  if (fromSource.value === 'list') {
    router.push('/opportunities')
    return
  }
  if (fromSource.value === 'detail') {
    router.push('/opportunities/' + opportunityId.value)
    return
  }
  if (typeof window !== 'undefined' && window.history.length > 1) {
    router.back()
    return
  }
  router.push('/opportunities/' + opportunityId.value)
}

function goOpportunity() {
  router.push('/opportunities/' + opportunityId.value)
}

function goStepManage() {
  router.push('/opportunities/' + opportunityId.value + '/steps')
}

function detectKind(file: File): LocalAttachment['kind'] {
  if (file.type.startsWith('image/'))
    return 'image'
  if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))
    return 'pdf'
  if (file.type.startsWith('audio/'))
    return 'audio'
  return 'other'
}

function clearAttachments() {
  for (const a of attachments.value)
    URL.revokeObjectURL(a.url)
  attachments.value = []
}

function addFiles(files: File[]) {
  if (!files.length)
    return

  const next = [...attachments.value]
  let skipped = 0

  for (const file of files) {
    if (next.length >= MAX_FILES) {
      skipped += 1
      continue
    }
    const dup = next.some(a => a.name === file.name && a.file.size === file.size)
    if (dup) {
      skipped += 1
      continue
    }
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
  if (skipped)
    notifyError(`حداکثر ${toFaDigits(MAX_FILES)} فایل. بعضی فایل‌ها اضافه نشدند.`)
}

function onFilesChange(e: Event) {
  const input = e.target as HTMLInputElement
  addFiles(Array.from(input.files || []))
  input.value = ''
}

function removeAttachment(id: string) {
  const item = attachments.value.find(a => a.id === id)
  if (item)
    URL.revokeObjectURL(item.url)
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
  if (isRecording.value) {
    stopRecording(true)
    return
  }
  if (attachments.value.length >= MAX_FILES) {
    notifyError(`حداکثر ${toFaDigits(MAX_FILES)} فایل.`)
    return
  }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    audioChunks = []
    mediaRecorder = new MediaRecorder(stream)
    mediaRecorder.ondataavailable = (ev) => {
      if (ev.data.size > 0)
        audioChunks.push(ev.data)
    }
    mediaRecorder.onstop = () => {
      stream.getTracks().forEach(t => t.stop())
      if (!audioChunks.length)
        return
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
      if (recordSeconds.value >= MAX_AUDIO_SEC)
        stopRecording(true)
    }, 1000)
  }
  catch {
    notifyError('دسترسی به میکروفون ممکن نشد.')
  }
}

function stopRecording(save: boolean) {
  if (recordTimer) {
    clearInterval(recordTimer)
    recordTimer = null
  }
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    if (!save)
      mediaRecorder.ondataavailable = null
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
  if (!body)
    return { summary: '', result: '' }
  const marker = 'نتیجه/واکنش:'
  const idx = body.indexOf(marker)
  if (idx === -1)
    return { summary: body, result: '' }
  return {
    summary: body.slice(0, idx).trim(),
    result: body.slice(idx + marker.length).trim(),
  }
}

async function resetFormClean() {
  suppressValidate.value = true
  form.value = {
    type: '',
    happened_on: todayIso(),
    body: '',
    result: '',
  }
  clearAttachments()
  await nextTick()
  formRef.value?.resetValidation?.()
  await nextTick()
  formRef.value?.resetValidation?.()
  suppressValidate.value = false
}

async function submit() {
  if (saving.value || suppressValidate.value)
    return

  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid)
    return

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
    await load()
    await resetFormClean()
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
    <div class="page-head d-flex flex-wrap align-center gap-2 mb-3">
      <VBtn
        icon
        variant="text"
        color="secondary"
        size="small"
        density="comfortable"
        :title="fromSource === 'list' ? 'بازگشت به لیست فرصت‌ها' : 'بازگشت'"
        @click="goBack"
      >
        <VIcon icon="ri-arrow-right-s-line" size="26" />
      </VBtn>
      <h1 class="page-title text-h6 font-weight-medium mb-0">
        <template v-if="detail?.organization_name">
          ثبت فعالیت برای «{{ detail.organization_name }}»
        </template>
        <template v-else>
          ثبت فعالیت
        </template>
      </h1>
    </div>

    <div v-if="loading" class="text-center py-8 text-medium-emphasis">
      در حال بارگذاری...
    </div>

    <template v-else-if="detail">
      <VRow dense class="equal-row align-stretch">
        <VCol cols="12" lg="8" class="d-flex">
          <VCard class="pa-3 pa-md-4 flex-grow-1 d-flex flex-column panel-card">
            <div class="text-subtitle-1 font-weight-medium mb-3">چه کاری انجام شد؟</div>

            <VForm ref="formRef" class="flex-grow-1 d-flex flex-column" @submit.prevent="submit">
              <VRow dense>
                <VCol cols="12" sm="6">
                  <VSelect
                    v-model="form.type"
                    :items="activityTypes"
                    label="نوع *"
                    density="compact"
                    hide-details="auto"
                    :rules="suppressValidate ? [] : [rules.required('نوع')]"
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <JalaliDateField
                    v-model="form.happened_on"
                    label="تاریخ *"
                    density="compact"
                    :rules="suppressValidate ? [] : [rules.required('تاریخ')]"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.body"
                    label="خلاصه عملکرد *"
                    placeholder="چه گفتید / چه شد؟"
                    rows="2"
                    density="compact"
                    hide-details="auto"
                    :rules="suppressValidate ? [] : [rules.required('خلاصه عملکرد')]"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.result"
                    label="نتیجه / واکنش (اختیاری)"
                    placeholder="واکنش مشتری یا خروجی ملموس"
                    rows="2"
                    density="compact"
                    hide-details="auto"
                  />
                </VCol>
              </VRow>

              <VCard variant="outlined" class="mt-2 pa-3 attachment-box">
                <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-2">
                  <div class="text-body-2 font-weight-medium">پیوست‌ها</div>
                  <div class="d-flex flex-wrap gap-2">
                    <VBtn size="small" density="comfortable" variant="tonal" color="primary" prepend-icon="ri-attachment-2" @click="fileInput?.click()">فایل</VBtn>
                    <VBtn size="small" density="comfortable" :variant="isRecording ? 'flat' : 'outlined'" :color="isRecording ? 'error' : 'secondary'" prepend-icon="ri-mic-line" @click="toggleRecord">
                      <template v-if="isRecording">{{ formatRecordTime(recordSeconds) }}</template>
                      <template v-else>ضبط</template>
                    </VBtn>
                    <input ref="fileInput" type="file" class="d-none" multiple accept="image/*,application/pdf,audio/*" @change="onFilesChange">
                  </div>
                </div>

                <div v-if="attachments.length" class="d-flex flex-wrap gap-2">
                  <div v-for="item in attachments" :key="item.id" class="attach-thumb">
                    <button type="button" class="attach-thumb__preview" :title="item.name" @click="openPreview(item)">
                      <img v-if="item.kind === 'image'" :src="item.url" :alt="item.name">
                      <div v-else class="attach-thumb__icon">
                        <VIcon :icon="item.kind === 'pdf' ? 'ri-file-pdf-2-line' : item.kind === 'audio' ? 'ri-mic-line' : 'ri-file-line'" size="22" />
                      </div>
                    </button>
                    <div class="attach-thumb__name text-truncate">{{ item.name }}</div>
                    <VBtn icon size="x-small" variant="text" class="attach-thumb__remove" @click="removeAttachment(item.id)">
                      <VIcon icon="ri-close-line" size="14" />
                    </VBtn>
                  </div>
                </div>
                <div v-else class="text-caption text-medium-emphasis">پیوستی انتخاب نشده</div>
              </VCard>

              <div class="d-flex flex-wrap justify-end gap-2 mt-auto pt-3">
                <VBtn variant="outlined" color="secondary" size="small" @click="goBack">انصراف</VBtn>
                <VBtn color="primary" size="small" type="button" :loading="saving" :disabled="saving" @click="submit">ثبت فعالیت</VBtn>
              </div>
            </VForm>
          </VCard>
        </VCol>

        <VCol cols="12" lg="4" class="d-flex">
          <div class="sidebar-stack flex-grow-1 d-flex flex-column gap-3">
            <VCard class="pa-3 opp-info-card">
              <button type="button" class="opp-name-link text-subtitle-1 font-weight-medium mb-2" @click="goOpportunity">
                {{ detail.organization_name }}
              </button>

              <div class="d-flex flex-wrap gap-1 mb-3">
                <VChip size="x-small" color="primary" variant="tonal">{{ toFaDigits(detail.code) }}</VChip>
                <VChip size="x-small" color="success" variant="tonal">{{ statusLabel[detail.status] || detail.status }}</VChip>
                <VChip v-if="detail.stage?.name" size="x-small" color="secondary" variant="tonal">{{ detail.stage.name }}</VChip>
              </div>

              <div class="step-block">
                <div class="text-caption text-medium-emphasis mb-1">گام جاری</div>
                <template v-if="currentStep">
                  <button type="button" class="step-title-link text-body-2 font-weight-medium" @click="goStepManage">
                    <span>{{ currentStep.title }}</span>
                    <VIcon icon="ri-arrow-left-s-line" size="18" class="flex-shrink-0" />
                  </button>
                  <div class="text-caption text-medium-emphasis mt-1">
                    مهلت: {{ formatJalaliFa(currentStep.due_on) || '—' }}
                  </div>
                </template>
                <div v-else class="text-caption text-medium-emphasis">گام بازی تعریف نشده</div>
              </div>
            </VCard>

            <VCard class="pa-0 overflow-hidden flex-grow-1 d-flex flex-column history-card">
              <div class="px-3 pt-3 pb-2 d-flex align-center justify-space-between">
                <span class="text-body-2 font-weight-medium">فعالیت‌های قبلی این گام</span>
                <VChip v-if="stepActivities.length" size="x-small" color="primary" variant="tonal">{{ toFaDigits(stepActivities.length) }}</VChip>
              </div>
              <VDivider />

              <div v-if="!stepActivities.length" class="pa-3 text-caption text-medium-emphasis">هنوز فعالیتی ثبت نشده.</div>

              <div v-else class="history-scroll flex-grow-1">
                <button v-for="a in stepActivities" :key="a.id" type="button" class="history-row" @click="openHistory(a)">
                  <div class="d-flex align-start justify-space-between gap-2">
                    <div class="min-w-0">
                      <div class="text-body-2 font-weight-medium">{{ typeLabel[a.type] || a.subject || a.type }}</div>
                      <div class="text-caption text-medium-emphasis text-truncate-2">{{ splitBody(a.body).summary || a.body }}</div>
                    </div>
                    <div class="text-caption text-medium-emphasis text-no-wrap">{{ formatJalaliFa(a.happened_at) }}</div>
                  </div>
                </button>
              </div>
            </VCard>
          </div>
        </VCol>
      </VRow>
    </template>

    <VDialog v-model="historyDetailOpen" max-width="520" scrollable>
      <VCard v-if="selectedHistory" class="activity-page">
        <VCardTitle class="d-flex align-center justify-space-between pa-4 pb-1 text-subtitle-1">
          <span>{{ typeLabel[selectedHistory.type] || selectedHistory.subject || 'فعالیت' }}</span>
          <VBtn icon variant="text" size="small" @click="historyDetailOpen = false"><VIcon icon="ri-close-line" /></VBtn>
        </VCardTitle>
        <VCardText class="pa-4 pt-2">
          <div class="d-flex flex-wrap gap-1 mb-3">
            <VChip size="x-small" variant="tonal" color="primary">{{ typeLabel[selectedHistory.type] || selectedHistory.type }}</VChip>
            <VChip size="x-small" variant="tonal">{{ formatJalaliFa(selectedHistory.happened_at) }}</VChip>
          </div>
          <div class="text-caption text-medium-emphasis mb-1">خلاصه عملکرد</div>
          <div class="text-body-2 mb-3 text-wrap">{{ splitBody(selectedHistory.body).summary || selectedHistory.body || '—' }}</div>
          <template v-if="splitBody(selectedHistory.body).result">
            <div class="text-caption text-medium-emphasis mb-1">نتیجه / واکنش</div>
            <div class="text-body-2 mb-3 text-wrap">{{ splitBody(selectedHistory.body).result }}</div>
          </template>
        </VCardText>
        <VCardActions class="pa-3 pt-0">
          <VSpacer />
          <VBtn color="primary" variant="tonal" size="small" @click="historyDetailOpen = false">بستن</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="previewOpen" max-width="720" scrollable>
      <VCard v-if="previewItem" class="activity-page">
        <VCardTitle class="d-flex align-center justify-space-between text-body-1">
          <span class="text-truncate">{{ previewItem.name }}</span>
          <div class="d-flex gap-1">
            <VBtn icon variant="text" size="small" @click="downloadAttachment(previewItem)"><VIcon icon="ri-download-line" /></VBtn>
            <VBtn icon variant="text" size="small" @click="previewOpen = false"><VIcon icon="ri-close-line" /></VBtn>
          </div>
        </VCardTitle>
        <VCardText>
          <img v-if="previewItem.kind === 'image'" :src="previewItem.url" class="preview-full" :alt="previewItem.name">
          <iframe v-else-if="previewItem.kind === 'pdf'" :src="previewItem.url" class="preview-pdf" title="PDF" />
          <audio v-else-if="previewItem.kind === 'audio'" :src="previewItem.url" controls class="w-100" />
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.activity-page {
  font-family: 'Vazirmatn', Vazirmatn, Tahoma, sans-serif !important;
}
.activity-page :deep(*),
.activity-page :deep(.v-btn),
.activity-page :deep(.v-field),
.activity-page :deep(.v-label),
.activity-page :deep(.v-chip) {
  font-family: 'Vazirmatn', Vazirmatn, Tahoma, sans-serif !important;
}
.page-title { line-height: 1.4; }
.equal-row { min-height: calc(100vh - 140px); }
@media (min-width: 1280px) {
  .panel-card,
  .sidebar-stack {
    min-height: calc(100vh - 140px);
    max-height: calc(100vh - 140px);
  }
  .history-card { min-height: 0; }
  .history-scroll { overflow-y: auto; min-height: 0; }
}
.opp-info-card {
  background: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-theme-primary), 0.22);
  box-shadow: none;
}
.opp-name-link {
  display: block;
  width: 100%;
  text-align: start;
  background: none;
  border: 0;
  padding: 0;
  cursor: pointer;
  font: inherit;
  color: rgb(var(--v-theme-primary));
  line-height: 1.4;
}
.opp-name-link:hover { text-decoration: underline; }
.step-block {
  padding-block-start: 0.5rem;
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.step-title-link {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
  text-align: start;
  background: none;
  border: 0;
  padding: 0;
  cursor: pointer;
  font: inherit;
  color: rgb(var(--v-theme-on-surface));
  line-height: 1.45;
}
.step-title-link:hover { color: rgb(var(--v-theme-primary)); }
.attachment-box { border-style: dashed !important; }
.history-row {
  display: block;
  width: 100%;
  text-align: start;
  background: transparent;
  border: 0;
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  padding: 10px 12px;
  cursor: pointer;
  font: inherit;
  color: inherit;
}
.history-row:hover { background: rgba(var(--v-theme-primary), 0.05); }
.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.attach-thumb { position: relative; width: 72px; }
.attach-thumb__preview {
  display: block;
  width: 72px;
  height: 56px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgba(var(--v-theme-on-surface), 0.04);
  cursor: pointer;
  padding: 0;
}
.attach-thumb__preview img { width: 100%; height: 100%; object-fit: cover; }
.attach-thumb__icon {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}
.attach-thumb__name { font-size: 0.65rem; margin-block-start: 2px; max-width: 72px; }
.attach-thumb__remove {
  position: absolute;
  inset-block-start: -6px;
  inset-inline-end: -6px;
  background: rgb(var(--v-theme-surface)) !important;
}
.preview-full { display: block; max-width: 100%; margin-inline: auto; border-radius: 8px; }
.preview-pdf { width: 100%; min-height: 55vh; border: 0; }
</style>
