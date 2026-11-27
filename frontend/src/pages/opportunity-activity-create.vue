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

const loading = ref(true)
const saving = ref(false)
const detail = ref<any | null>(null)
const formRef = ref()

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

const MAX_AUDIO_SEC = 180
const isRecording = ref(false)
const recordSeconds = ref(0)
let mediaRecorder: MediaRecorder | null = null
let recordTimer: ReturnType<typeof setInterval> | null = null
let audioChunks: BlobPart[] = []

/** جزئیات فعالیت قبلی */
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
  if (!stepId)
    return d.activities
  return d.activities.filter((a: any) => a.step_id === stepId || !a.step_id)
})

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
  router.push('/opportunities/' + opportunityId.value)
}

function goList() {
  router.push('/opportunities')
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
    if (kind === 'image' && next.some(a => a.kind === 'image'))
      continue
    if (kind === 'pdf' && next.some(a => a.kind === 'pdf'))
      continue
    if (kind === 'audio' && next.some(a => a.kind === 'audio'))
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

async function submit() {
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
    form.value = {
      type: '',
      happened_on: todayIso(),
      body: '',
      result: '',
    }
    for (const a of attachments.value)
      URL.revokeObjectURL(a.url)
    attachments.value = []
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
  for (const a of attachments.value)
    URL.revokeObjectURL(a.url)
})
</script>

<template>
  <div class="activity-page">
    <!-- هدر صفحه -->
    <div class="d-flex flex-wrap align-start justify-space-between gap-4 mb-6">
      <div>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <VBtn
            variant="text"
            color="secondary"
            size="small"
            prepend-icon="ri-arrow-right-line"
            @click="goBack"
          >
            بازگشت به فرصت
          </VBtn>
          <VBtn
            variant="text"
            color="secondary"
            size="small"
            @click="goList"
          >
            لیست فرصت‌ها
          </VBtn>
        </div>
        <h4 class="text-h4 mb-1">
          ثبت فعالیت
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          کار انجام‌شده زیر <strong>گام جاری</strong> را ثبت کنید (تماس، جلسه، ارسال...).
        </p>
      </div>
      <VChip
        color="primary"
        variant="tonal"
        size="small"
        prepend-icon="ri-question-line"
      >
        راهنمای ثبت فعالیت
      </VChip>
    </div>

    <div
      v-if="loading"
      class="text-center py-12 text-medium-emphasis"
    >
      در حال بارگذاری...
    </div>

    <template v-else-if="detail">
      <VRow>
        <!-- فرم اصلی -->
        <VCol
          cols="12"
          lg="8"
        >
          <VCard class="pa-5 pa-md-6 mb-4">
            <div class="text-h6 mb-1">
              چه کاری انجام شد؟
            </div>
            <p class="text-body-2 text-medium-emphasis mb-6">
              این فعالیت زیر گام جاری ذخیره می‌شود و به پیشروی گام کمک می‌کند.
            </p>

            <VForm
              ref="formRef"
              @submit.prevent="submit"
            >
              <VRow>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VSelect
                    v-model="form.type"
                    :items="activityTypes"
                    label="نوع *"
                    placeholder="— انتخاب —"
                    :rules="[rules.required('نوع')]"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <JalaliDateField
                    v-model="form.happened_on"
                    label="تاریخ *"
                    :rules="[rules.required('تاریخ')]"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.body"
                    label="خلاصه عملکرد *"
                    placeholder="چه گفتید / چه شد؟"
                    rows="4"
                    auto-grow
                    :rules="[rules.required('خلاصه عملکرد')]"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.result"
                    label="نتیجه / واکنش (اختیاری)"
                    placeholder="واکنش مشتری یا خروجی ملموس"
                    rows="3"
                    auto-grow
                  />
                </VCol>
              </VRow>

              <VCard
                variant="outlined"
                class="mt-2 pa-5 attachment-box"
              >
                <div class="text-subtitle-1 font-weight-medium mb-1">
                  پیوست‌های فعالیت
                </div>
                <div class="text-body-2 text-medium-emphasis mb-4">
                  حداکثر ۱ تصویر، ۱ PDF، ۱ صوت (تا ۳ دقیقه). برای دیدن بزرگ‌تر روی بندانگشتی کلیک کنید.
                </div>

                <div class="d-flex flex-wrap gap-3 mb-4">
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
                  class="d-flex flex-wrap gap-3"
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
                        <VIcon
                          icon="ri-file-pdf-2-line"
                          size="28"
                        />
                        <span>PDF</span>
                      </div>
                      <div
                        v-else-if="item.kind === 'audio'"
                        class="attach-thumb__icon"
                      >
                        <VIcon
                          icon="ri-mic-line"
                          size="28"
                        />
                        <span>صوت</span>
                      </div>
                      <div
                        v-else
                        class="attach-thumb__icon"
                      >
                        <VIcon
                          icon="ri-file-line"
                          size="28"
                        />
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
                      <VIcon
                        icon="ri-close-line"
                        size="14"
                      />
                    </VBtn>
                  </div>
                </div>
                <div
                  v-else
                  class="text-body-2 text-medium-emphasis"
                >
                  هنوز پیوستی نیست.
                </div>
              </VCard>

              <div class="d-flex flex-wrap justify-end gap-3 mt-6">
                <VBtn
                  variant="outlined"
                  color="secondary"
                  @click="goBack"
                >
                  انصراف
                </VBtn>
                <VBtn
                  color="primary"
                  :loading="saving"
                  @click="submit"
                >
                  ثبت فعالیت
                </VBtn>
              </div>
            </VForm>
          </VCard>
        </VCol>

        <!-- ستون کناری: گام + تاریخچه -->
        <VCol
          cols="12"
          lg="4"
        >
          <VCard
            class="pa-5 mb-4"
            color="primary"
            variant="tonal"
          >
            <div class="text-overline mb-1">
              فرصت
            </div>
            <div class="text-h6 mb-3">
              {{ detail.organization_name }}
            </div>
            <div class="d-flex flex-wrap gap-2 mb-5">
              <VChip
                size="small"
                variant="flat"
              >
                {{ toFaDigits(detail.code) }}
              </VChip>
              <VChip
                size="small"
                variant="flat"
              >
                {{ statusLabel[detail.status] || detail.status }}
              </VChip>
              <VChip
                v-if="detail.stage?.name"
                size="small"
                variant="flat"
              >
                {{ detail.stage.name }}
              </VChip>
            </div>

            <div class="text-overline mb-1">
              گام جاری
            </div>
            <template v-if="currentStep">
              <div class="text-body-1 font-weight-medium mb-1">
                {{ currentStep.title }}
              </div>
              <div class="text-body-2 text-medium-emphasis mb-3">
                مهلت: {{ formatJalaliFa(currentStep.due_on) || '—' }}
              </div>
            </template>
            <div
              v-else
              class="text-body-2 text-medium-emphasis mb-3"
            >
              گام بازی تعریف نشده است.
            </div>
            <div class="text-body-2 text-medium-emphasis">
              هر فعالیت زیر همین گام ثبت می‌شود.
            </div>
          </VCard>

          <VCard class="pa-0 overflow-hidden">
            <div class="pa-4 pb-2 d-flex align-center justify-space-between">
              <div class="text-subtitle-1 font-weight-medium">
                فعالیت‌های قبلی این گام
              </div>
              <VChip
                v-if="stepActivities.length"
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ toFaDigits(stepActivities.length) }}
              </VChip>
            </div>
            <div class="text-caption text-medium-emphasis px-4 pb-3">
              برای دیدن جزئیات کامل روی هر مورد کلیک کنید.
            </div>

            <VDivider />

            <div
              v-if="!stepActivities.length"
              class="pa-5 text-body-2 text-medium-emphasis"
            >
              هنوز فعالیتی برای این گام ثبت نشده.
            </div>

            <VList
              v-else
              lines="three"
              class="py-0"
            >
              <VListItem
                v-for="a in stepActivities"
                :key="a.id"
                class="history-list-item"
                @click="openHistory(a)"
              >
                <VListItemTitle class="font-weight-medium">
                  {{ typeLabel[a.type] || a.subject || a.type }}
                </VListItemTitle>
                <VListItemSubtitle class="text-wrap mt-1">
                  {{ splitBody(a.body).summary || a.body }}
                </VListItemSubtitle>
                <template #append>
                  <div class="d-flex flex-column align-end gap-1">
                    <span class="text-caption text-medium-emphasis">
                      {{ formatJalaliFa(a.happened_at) }}
                    </span>
                    <VIcon
                      icon="ri-arrow-left-s-line"
                      size="18"
                      class="text-medium-emphasis"
                    />
                  </div>
                </template>
              </VListItem>
            </VList>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <!-- پاپ‌آپ جزئیات فعالیت قبلی -->
    <VDialog
      v-model="historyDetailOpen"
      max-width="560"
      scrollable
    >
      <VCard v-if="selectedHistory">
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-2">
          <span>{{ typeLabel[selectedHistory.type] || selectedHistory.subject || 'فعالیت' }}</span>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="historyDetailOpen = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>
        <VCardText class="pa-5 pt-2">
          <div class="d-flex flex-wrap gap-2 mb-4">
            <VChip
              size="small"
              variant="tonal"
              color="primary"
            >
              {{ typeLabel[selectedHistory.type] || selectedHistory.type }}
            </VChip>
            <VChip
              size="small"
              variant="tonal"
            >
              {{ formatJalaliFa(selectedHistory.happened_at) }}
            </VChip>
          </div>

          <div class="text-overline mb-1">
            خلاصه عملکرد
          </div>
          <div class="text-body-1 mb-4 text-wrap">
            {{ splitBody(selectedHistory.body).summary || selectedHistory.body || '—' }}
          </div>

          <template v-if="splitBody(selectedHistory.body).result">
            <div class="text-overline mb-1">
              نتیجه / واکنش
            </div>
            <div class="text-body-1 mb-4 text-wrap">
              {{ splitBody(selectedHistory.body).result }}
            </div>
          </template>

          <div class="text-overline mb-2">
            پیوست‌ها
          </div>
          <div class="text-body-2 text-medium-emphasis">
            <template v-if="selectedHistory.attachments?.length">
              <!-- وقتی API پیوست داشته باشد -->
              <div class="d-flex flex-wrap gap-2">
                <VChip
                  v-for="(f, i) in selectedHistory.attachments"
                  :key="i"
                  size="small"
                  variant="outlined"
                  prepend-icon="ri-attachment-2"
                >
                  {{ f.name || f }}
                </VChip>
              </div>
            </template>
            <template v-else>
              پیوستی برای این فعالیت ذخیره نشده است.
            </template>
          </div>
        </VCardText>
        <VCardActions class="pa-5 pt-0">
          <VSpacer />
          <VBtn
            color="primary"
            variant="tonal"
            @click="historyDetailOpen = false"
          >
            بستن
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- پیش‌نمایش پیوست جدید -->
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
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.activity-page {
  font-family: Vazirmatn, Tahoma, sans-serif;
}

.activity-page :deep(*) {
  font-family: inherit;
}

.attachment-box {
  border-style: dashed !important;
}

.history-list-item {
  cursor: pointer;
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.history-list-item:hover {
  background: rgba(var(--v-theme-primary), 0.04);
}

.attach-thumb {
  position: relative;
  width: 96px;
}

.attach-thumb__preview {
  display: block;
  width: 96px;
  height: 80px;
  border-radius: 10px;
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
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.attach-thumb__name {
  font-size: 0.72rem;
  margin-block-start: 6px;
  max-width: 96px;
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
</style>
