<script setup lang="ts">
import { useDisplay } from 'vuetify'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import { fetchOpportunity } from '@/utils/api'
import { formatJalaliFa, toFaDigits, todayIso } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const props = defineProps<{
  modelValue: boolean
  opportunity: any | null
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'submit', payload: {
    type: string
    subject: string
    body: string
    result?: string | null
    happened_at?: string | null
    step_id?: number | null
  }): void
}>()

const { mobile } = useDisplay()
const { error: notifyError } = useNotify()

const formRef = ref()
const dirty = ref(false)
const loadingDetail = ref(false)
const detail = ref<any | null>(null)
const historyOpen = ref(false)

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

const form = ref({
  type: '' as string,
  happened_on: todayIso() as string | null,
  body: '',
  result: '',
})

/** پیوست‌های محلی برای پیش‌نمایش */
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

/** ضبط صوت */
const MAX_AUDIO_SEC = 180
const isRecording = ref(false)
const recordSeconds = ref(0)
let mediaRecorder: MediaRecorder | null = null
let recordTimer: ReturnType<typeof setInterval> | null = null
let audioChunks: BlobPart[] = []

const statusLabel: Record<string, string> = {
  active: 'فعال',
  nurturing: 'در حال پرورش',
  paused: 'متوقف موقت',
  won: 'بسته‌شده-موفق',
  lost: 'بسته‌شده-ناموفق',
}

const currentStep = computed(() => {
  const d = detail.value || props.opportunity
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
  if (!stepId)
    return d.activities
  return d.activities.filter((a: any) => a.step_id === stepId || !a.step_id)
})

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) {
      stopRecording(false)
      clearAttachments()
      return
    }
    form.value = {
      type: '',
      happened_on: todayIso(),
      body: '',
      result: '',
    }
    clearAttachments()
    dirty.value = false
    historyOpen.value = false
    detail.value = null
    if (props.opportunity?.id)
      await loadDetail(props.opportunity.id)
  },
)

watch(form, () => { dirty.value = true }, { deep: true })

async function loadDetail(id: number) {
  loadingDetail.value = true
  try {
    const res = await fetchOpportunity(id)
    detail.value = res.data
  }
  catch (e: any) {
    notifyError(e?.message || 'خطا در بارگذاری گام و فعالیت‌ها')
    detail.value = props.opportunity
  }
  finally {
    loadingDetail.value = false
  }
}

function close() {
  stopRecording(false)
  emit('update:modelValue', false)
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

function addFiles(files: File[]) {
  const next = [...attachments.value]
  for (const file of files) {
    const kind = detectKind(file)
    const hasImage = next.some(a => a.kind === 'image')
    const hasPdf = next.some(a => a.kind === 'pdf')
    const hasAudio = next.some(a => a.kind === 'audio')
    if (kind === 'image' && hasImage)
      continue
    if (kind === 'pdf' && hasPdf)
      continue
    if (kind === 'audio' && hasAudio)
      continue
    if (next.length >= 3)
      break
    next.push({
      id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
      file,
      kind,
      url: URL.createObjectURL(file),
      name: file.name,
    })
  }
  attachments.value = next
  dirty.value = true
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

function clearAttachments() {
  for (const a of attachments.value)
    URL.revokeObjectURL(a.url)
  attachments.value = []
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
  if (attachments.value.some(a => a.kind === 'audio')) {
    notifyError('یک فایل صوتی از قبل هست. حذف کنید و دوباره ضبط کنید.')
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

async function submit() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid)
    return

  const typeTitle = activityTypes.find(t => t.value === form.value.type)?.title || form.value.type

  emit('submit', {
    type: form.value.type || 'note',
    subject: typeTitle,
    body: form.value.body,
    result: form.value.result || null,
    happened_at: form.value.happened_on || null,
    step_id: currentStep.value?.id ?? null,
  })
}

onBeforeUnmount(() => {
  stopRecording(false)
  clearAttachments()
})
</script>

<template>
  <VDialog
    :model-value="modelValue"
    :fullscreen="mobile"
    :max-width="mobile ? undefined : 980"
    scrollable
    persistent
    content-class="activity-form-dialog-wrap"
    @update:model-value="(v: boolean) => emit('update:modelValue', v)"
  >
    <VCard class="activity-form-dialog d-flex flex-column">
      <!-- هدر -->
      <div class="pa-4 pa-md-5 pb-2 d-flex flex-wrap align-start justify-space-between gap-3">
        <div>
          <div class="text-h5 font-weight-medium">
            ثبت فعالیت
          </div>
          <div class="text-body-2 text-medium-emphasis">
            کار انجام‌شده زیر <strong>گام جاری</strong> را ثبت کنید.
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VChip
            color="primary"
            variant="tonal"
            size="small"
            prepend-icon="ri-question-line"
          >
            راهنمای ثبت فعالیت
          </VChip>
          <VBtn
            v-if="mobile"
            icon
            variant="text"
            size="small"
            @click="close"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </div>
      </div>

      <VCardText class="pa-4 pa-md-5 pt-2 flex-grow-1 overflow-y-auto">
        <VRow>
          <!-- موبایل: کارت گام/فرصت اول -->
          <VCol
            cols="12"
            md="4"
            :order="mobile ? 0 : 1"
          >
            <VCard
              class="opportunity-side-card pa-4 mb-4 mb-md-0"
              color="primary"
              variant="tonal"
            >
              <div class="text-overline mb-1">
                فرصت
              </div>
              <div class="text-h6 mb-2">
                {{ (detail || opportunity)?.organization_name || '—' }}
              </div>
              <div class="d-flex flex-wrap gap-1 mb-4">
                <VChip size="x-small" variant="flat">
                  {{ toFaDigits((detail || opportunity)?.code || '') }}
                </VChip>
                <VChip size="x-small" variant="flat">
                  {{ statusLabel[(detail || opportunity)?.status] || (detail || opportunity)?.status || '—' }}
                </VChip>
                <VChip
                  v-if="(detail || opportunity)?.stage?.name"
                  size="x-small"
                  variant="flat"
                >
                  {{ (detail || opportunity).stage.name }}
                </VChip>
              </div>

              <div class="text-overline mb-1">
                گام جاری
              </div>
              <div v-if="loadingDetail" class="text-caption text-medium-emphasis mb-2">
                در حال بارگذاری...
              </div>
              <template v-else-if="currentStep">
                <div class="text-body-2 font-weight-medium mb-1">
                  {{ currentStep.title || currentStep }}
                </div>
                <div class="text-caption text-medium-emphasis mb-2">
                  مهلت:
                  {{ formatJalaliFa(currentStep.due_on) || '—' }}
                </div>
              </template>
              <div
                v-else
                class="text-body-2 text-medium-emphasis mb-2"
              >
                گام بازی تعریف نشده است.
              </div>
              <div class="text-caption text-medium-emphasis mb-3">
                هر فعالیت زیر همین گام ثبت می‌شود و به پیشروی گام کمک می‌کند.
              </div>

              <VBtn
                variant="text"
                color="primary"
                size="small"
                class="px-0"
                :to="opportunity ? { path: '/opportunities/' + opportunity.id } : undefined"
                @click="close"
              >
                بازگشت به فرصت
              </VBtn>
            </VCard>

            <!-- تاریخچه فعالیت‌های همین گام -->
            <VExpansionPanels
              v-model="historyOpen"
              class="mt-0 mt-md-4 history-panels"
              variant="accordion"
            >
              <VExpansionPanel>
                <VExpansionPanelTitle class="text-body-2">
                  فعالیت‌های قبلی این گام
                  <VChip
                    v-if="stepActivities.length"
                    size="x-small"
                    class="ms-2"
                    color="primary"
                    variant="tonal"
                  >
                    {{ toFaDigits(stepActivities.length) }}
                  </VChip>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <div v-if="loadingDetail" class="text-caption text-medium-emphasis">
                    در حال بارگذاری...
                  </div>
                  <div
                    v-else-if="!stepActivities.length"
                    class="text-caption text-medium-emphasis"
                  >
                    هنوز فعالیتی برای این گام ثبت نشده.
                  </div>
                  <div
                    v-else
                    class="d-flex flex-column gap-3"
                  >
                    <div
                      v-for="a in stepActivities"
                      :key="a.id"
                      class="history-item"
                    >
                      <div class="d-flex align-center justify-space-between gap-2 mb-1">
                        <span class="text-body-2 font-weight-medium">
                          {{ typeLabel[a.type] || a.subject || a.type }}
                        </span>
                        <span class="text-caption text-medium-emphasis">
                          {{ formatJalaliFa(a.happened_at) }}
                        </span>
                      </div>
                      <div class="text-body-2 text-medium-emphasis text-wrap">
                        {{ a.body }}
                      </div>
                    </div>
                  </div>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </VCol>

          <!-- فرم -->
          <VCol
            cols="12"
            md="8"
            :order="mobile ? 1 : 0"
          >
            <VCard
              variant="outlined"
              class="pa-4"
            >
              <div class="text-subtitle-1 font-weight-medium mb-4">
                چه کاری انجام شد؟
              </div>

              <VForm
                ref="formRef"
                @submit.prevent="submit"
              >
                <VRow>
                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <VSelect
                      v-model="form.type"
                      :items="activityTypes"
                      label="نوع"
                      placeholder="— انتخاب —"
                      :rules="[rules.required('نوع')]"
                    />
                  </VCol>
                  <VCol
                    cols="12"
                    sm="6"
                  >
                    <JalaliDateField
                      v-model="form.happened_on"
                      label="تاریخ"
                      :rules="[rules.required('تاریخ')]"
                    />
                  </VCol>
                  <VCol cols="12">
                    <VTextarea
                      v-model="form.body"
                      label="خلاصه عملکرد"
                      placeholder="چه گفتید / چه شد؟"
                      rows="3"
                      auto-grow
                      :rules="[rules.required('خلاصه عملکرد')]"
                    />
                  </VCol>
                  <VCol cols="12">
                    <VTextarea
                      v-model="form.result"
                      label="نتیجه / واکنش (اختیاری)"
                      placeholder="واکنش مشتری یا خروجی ملموس"
                      rows="2"
                      auto-grow
                    />
                  </VCol>
                </VRow>

                <VCard
                  variant="outlined"
                  class="mt-2 pa-4 attachment-box"
                >
                  <div class="text-subtitle-2 font-weight-medium mb-1">
                    پیوست‌های فعالیت
                  </div>
                  <div class="text-caption text-medium-emphasis mb-3">
                    حداکثر ۱ تصویر، ۱ PDF، ۱ صوت (تا ۳ دقیقه). برای تعویض، حذف کنید و دوباره اضافه کنید.
                  </div>

                  <div class="d-flex flex-wrap gap-2 mb-3">
                    <VBtn
                      variant="tonal"
                      color="primary"
                      prepend-icon="ri-attachment-2"
                      @click="fileInput?.click()"
                    >
                      افزودن فایل
                    </VBtn>
                    <VBtn
                      :variant="isRecording ? 'flat' : 'outlined'"
                      :color="isRecording ? 'error' : 'secondary'"
                      prepend-icon="ri-mic-line"
                      @click="toggleRecord"
                    >
                      <template v-if="isRecording">
                        توقف · {{ formatRecordTime(recordSeconds) }}
                      </template>
                      <template v-else>
                        ضبط صوت (تا ۳ دقیقه)
                      </template>
                    </VBtn>
                    <input
                      ref="fileInput"
                      type="file"
                      class="d-none"
                      multiple
                      accept="image/*,application/pdf,audio/*"
                      @change="onFilesChange"
                    >
                  </div>

                  <div
                    v-if="attachments.length"
                    class="d-flex flex-wrap gap-2"
                  >
                    <div
                      v-for="item in attachments"
                      :key="item.id"
                      class="attach-thumb"
                    >
                      <button
                        type="button"
                        class="attach-thumb__preview"
                        @click="openPreview(item)"
                      >
                        <img
                          v-if="item.kind === 'image'"
                          :src="item.url"
                          :alt="item.name"
                        >
                        <div
                          v-else-if="item.kind === 'pdf'"
                          class="attach-thumb__icon"
                        >
                          <VIcon icon="ri-file-pdf-2-line" size="28" />
                          <span>PDF</span>
                        </div>
                        <div
                          v-else-if="item.kind === 'audio'"
                          class="attach-thumb__icon"
                        >
                          <VIcon icon="ri-mic-line" size="28" />
                          <span>صوت</span>
                        </div>
                        <div
                          v-else
                          class="attach-thumb__icon"
                        >
                          <VIcon icon="ri-file-line" size="28" />
                        </div>
                      </button>
                      <div class="attach-thumb__name text-truncate">
                        {{ item.name }}
                      </div>
                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        class="attach-thumb__remove"
                        @click="removeAttachment(item.id)"
                      >
                        <VIcon icon="ri-close-line" size="14" />
                      </VBtn>
                    </div>
                  </div>
                  <div
                    v-else
                    class="text-caption text-medium-emphasis"
                  >
                    هنوز پیوستی نیست.
                  </div>
                </VCard>
              </VForm>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>

      <div class="pa-4 pa-md-5 pt-2 d-flex flex-wrap justify-end gap-2 form-actions">
        <VBtn
          variant="outlined"
          color="secondary"
          @click="close"
        >
          انصراف
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          @click="submit"
        >
          ثبت فعالیت
        </VBtn>
      </div>
    </VCard>

    <!-- پیش‌نمایش پیوست -->
    <VDialog
      v-model="previewOpen"
      max-width="720"
      scrollable
    >
      <VCard v-if="previewItem">
        <VCardTitle class="d-flex align-center justify-space-between">
          <span class="text-body-1 text-truncate">{{ previewItem.name }}</span>
          <div class="d-flex gap-1">
            <VBtn
              icon
              variant="text"
              size="small"
              title="دانلود"
              @click="downloadAttachment(previewItem)"
            >
              <VIcon icon="ri-download-line" />
            </VBtn>
            <VBtn
              icon
              variant="text"
              size="small"
              @click="previewOpen = false"
            >
              <VIcon icon="ri-close-line" />
            </VBtn>
          </div>
        </VCardTitle>
        <VCardText>
          <img
            v-if="previewItem.kind === 'image'"
            :src="previewItem.url"
            class="preview-full"
            :alt="previewItem.name"
          >
          <iframe
            v-else-if="previewItem.kind === 'pdf'"
            :src="previewItem.url"
            class="preview-pdf"
            title="PDF"
          />
          <audio
            v-else-if="previewItem.kind === 'audio'"
            :src="previewItem.url"
            controls
            class="w-100"
          />
          <div
            v-else
            class="text-medium-emphasis"
          >
            پیش‌نمایش این نوع فایل در دسترس نیست. دانلود کنید.
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </VDialog>
</template>

<style scoped>
.activity-form-dialog {
  font-family: Vazirmatn, Tahoma, sans-serif !important;
  max-height: 100%;
}

.activity-form-dialog :deep(*) {
  font-family: inherit;
}

.attachment-box {
  border-style: dashed !important;
}

.opportunity-side-card {
  position: sticky;
  top: 0.5rem;
}

.form-actions {
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgb(var(--v-theme-surface));
  position: sticky;
  bottom: 0;
  z-index: 2;
  padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)) !important;
}

.history-item {
  padding-block-end: 0.75rem;
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.history-item:last-child {
  border-block-end: none;
  padding-block-end: 0;
}

.attach-thumb {
  position: relative;
  width: 88px;
}

.attach-thumb__preview {
  display: block;
  width: 88px;
  height: 72px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: rgba(var(--v-theme-on-surface), 0.04);
  cursor: pointer;
  padding: 0;
}

.attach-thumb__preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.attach-thumb__icon {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  font-size: 0.7rem;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.attach-thumb__name {
  font-size: 0.7rem;
  margin-block-start: 4px;
  max-width: 88px;
}

.attach-thumb__remove {
  position: absolute;
  inset-block-start: -6px;
  inset-inline-end: -6px;
  background: rgb(var(--v-theme-surface)) !important;
}

.preview-full {
  display: block;
  max-width: 100%;
  margin-inline: auto;
  border-radius: 8px;
}

.preview-pdf {
  width: 100%;
  min-height: 60vh;
  border: 0;
}

@media (max-width: 960px) {
  .opportunity-side-card {
    position: static;
  }
}
</style>
