<script setup lang="ts">
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { rules } from '@/utils/formRules'
import { formatJalaliFa, toFaDigits, todayIso } from '@/utils/fa'

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
  }): void
}>()

const formRef = ref()
const dirty = ref(false)

const activityTypes = [
  { title: 'تماس تلفنی', value: 'call' },
  { title: 'جلسه', value: 'meeting' },
  { title: 'ایمیل / پیام', value: 'message' },
  { title: 'بازدید', value: 'visit' },
  { title: 'ارسال پیشنهاد', value: 'proposal' },
  { title: 'سایر', value: 'other' },
]

const form = ref({
  type: '' as string,
  happened_on: todayIso() as string | null,
  body: '',
  result: '',
})

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])

const statusLabel: Record<string, string> = {
  active: 'فعال',
  nurturing: 'در حال پرورش',
  paused: 'متوقف موقت',
  won: 'بسته‌شده-موفق',
  lost: 'بسته‌شده-ناموفق',
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      form.value = {
        type: '',
        happened_on: todayIso(),
        body: '',
        result: '',
      }
      selectedFiles.value = []
      dirty.value = false
    }
  },
)

watch(form, () => { dirty.value = true }, { deep: true })

function close() {
  emit('update:modelValue', false)
}

function onFilesChange(e: Event) {
  const input = e.target as HTMLInputElement
  const files = Array.from(input.files || [])
  // محدودیت ساده مطابق راهنما: حداکثر چند فایل سبک
  selectedFiles.value = files.slice(0, 3)
  dirty.value = true
}

function removeFile(index: number) {
  selectedFiles.value = selectedFiles.value.filter((_, i) => i !== index)
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
  })
}
</script>

<template>
  <VDialog
    :model-value="modelValue"
    max-width="960"
    scrollable
    persistent
    @update:model-value="(v: boolean) => emit('update:modelValue', v)"
  >
    <VCard class="activity-form-dialog">
      <VCardTitle class="d-flex flex-wrap align-center justify-space-between gap-3 pa-5 pb-2">
        <div>
          <div class="text-h5 font-weight-medium">
            ثبت فعالیت
          </div>
          <div class="text-body-2 text-medium-emphasis">
            رویداد انجام‌شده را ثبت کنید (تماس، جلسه، ارسال...).
          </div>
        </div>
        <VChip
          color="primary"
          variant="tonal"
          size="small"
          prepend-icon="ri-question-line"
        >
          راهنمای ثبت فعالیت
        </VChip>
      </VCardTitle>

      <VCardText class="pa-5 pt-2">
        <VRow>
          <VCol cols="12" md="8">
            <VCard
              variant="outlined"
              class="pa-4"
            >
              <div class="text-subtitle-1 font-weight-medium mb-4">
                چه کاری انجام شد؟
              </div>

              <VForm ref="formRef" @submit.prevent="submit">
                <VRow>
                  <VCol cols="12" sm="6">
                    <VSelect
                      v-model="form.type"
                      :items="activityTypes"
                      label="نوع"
                      placeholder="— انتخاب —"
                      :rules="[rules.required('نوع')]"
                    />
                  </VCol>
                  <VCol cols="12" sm="6">
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
                    حداکثر ۱ تصویر، ۱ PDF، صوت (تا ۲ دقیقه). برای تعویض، حذف کنید و دوباره اضافه کنید.
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
                      variant="outlined"
                      color="secondary"
                      prepend-icon="ri-mic-line"
                      disabled
                    >
                      ضبط صوت (تا ۲ دقیقه)
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

                  <div v-if="selectedFiles.length" class="d-flex flex-column gap-2">
                    <div
                      v-for="(f, i) in selectedFiles"
                      :key="i"
                      class="d-flex align-center justify-space-between gap-2 text-body-2"
                    >
                      <span class="text-truncate">{{ f.name }}</span>
                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        @click="removeFile(i)"
                      >
                        <VIcon icon="ri-close-line" size="16" />
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

          <VCol cols="12" md="4">
            <VCard
              class="opportunity-side-card pa-4"
              color="primary"
              variant="tonal"
            >
              <div class="text-overline mb-1">
                فرصت
              </div>
              <div class="text-h6 mb-2">
                {{ opportunity?.organization_name || '—' }}
              </div>
              <div class="d-flex flex-wrap gap-1 mb-4">
                <VChip size="x-small" variant="flat">
                  {{ toFaDigits(opportunity?.code || '') }}
                </VChip>
                <VChip size="x-small" variant="flat">
                  {{ statusLabel[opportunity?.status] || opportunity?.status || '—' }}
                </VChip>
                <VChip
                  v-if="opportunity?.stage?.name"
                  size="x-small"
                  variant="flat"
                >
                  {{ opportunity.stage.name }}
                </VChip>
              </div>

              <div class="text-overline mb-1">
                اقدام باز
              </div>
              <div class="text-body-2 font-weight-medium mb-1">
                {{ opportunity?.next_step || opportunity?.current_step?.title || '—' }}
              </div>
              <div class="text-caption text-medium-emphasis mb-4">
                مهلت:
                {{ formatJalaliFa(opportunity?.next_step_due_on || opportunity?.current_step?.due_on) || '—' }}
              </div>
              <div class="text-caption text-medium-emphasis mb-4">
                فعالیتی که ثبت می‌کنید به این اقدام وصل می‌شود.
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
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-5 pt-0 justify-end gap-2">
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
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.attachment-box {
  border-style: dashed !important;
}
.opportunity-side-card {
  position: sticky;
  top: 1rem;
}
</style>
