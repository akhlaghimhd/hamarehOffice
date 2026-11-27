<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  closeOpportunityStep,
  fetchOpportunity,
  fetchOpportunityStages,
  getCompany,
} from '@/utils/api'
import { formatJalaliFa, toFaDigits } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotify()
const id = computed(() => Number(route.params.id))

const item = ref<any | null>(null)
const stages = ref<any[]>([])
const loading = ref(false)
const closeOpen = ref(false)
const closing = ref(false)
const formRef = ref()
const dirty = ref(false)

const closeForm = ref({
  result: '',
  stage_id: null as number | null,
  status: 'active',
  next_step_title: '',
  next_step_due_on: null as string | null,
})

watch(closeForm, () => { dirty.value = true }, { deep: true })

const statusLabel: Record<string, string> = {
  active: 'فعال',
  nurturing: 'در حال پرورش',
  paused: 'متوقف موقت',
  won: 'بسته‌شده-موفق',
  lost: 'بسته‌شده-ناموفق',
}

const currentStep = computed(() => {
  const d = item.value
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

const closedSteps = computed(() =>
  (item.value?.steps || []).filter((s: any) => s.status === 'closed'),
)

async function load() {
  if (!getCompany()?.id) {
    notifyError('شرکت جاری مشخص نشده است.')
    return
  }
  loading.value = true
  try {
    const [res, stageRes] = await Promise.all([
      fetchOpportunity(id.value),
      fetchOpportunityStages(),
    ])
    item.value = res.data
    stages.value = (stageRes.data || []).map((s: any) => ({ title: s.name, value: s.id }))
    closeForm.value.stage_id = res.data?.stage?.id ?? null
    closeForm.value.status = res.data?.status || 'active'
    dirty.value = false
  }
  catch (e: any) {
    notifyError(e?.message || 'خطا در بارگذاری')
  }
  finally {
    loading.value = false
  }
}

function goBack() {
  if (typeof window !== 'undefined' && window.history.length > 1)
    router.back()
  else
    router.push('/opportunities/' + id.value)
}

function goOpportunity() {
  router.push('/opportunities/' + id.value)
}

function openClose() {
  closeForm.value.result = ''
  closeForm.value.next_step_title = ''
  closeForm.value.next_step_due_on = null
  closeForm.value.stage_id = item.value?.stage?.id ?? null
  closeForm.value.status = item.value?.status || 'active'
  dirty.value = false
  closeOpen.value = true
}

async function submitClose() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid)
    return

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
  }
  catch (e: any) {
    notifyError(e?.message || 'بستن گام ناموفق')
  }
  finally {
    closing.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="steps-page">
    <div class="d-flex flex-wrap align-center gap-2 mb-4">
      <VBtn
        icon
        variant="text"
        color="secondary"
        size="small"
        @click="goBack"
      >
        <VIcon
          icon="ri-arrow-right-s-line"
          size="26"
        />
      </VBtn>
      <div>
        <h1 class="text-h6 font-weight-medium mb-0">
          مدیریت گام‌ها
        </h1>
        <button
          v-if="item"
          type="button"
          class="link-btn text-body-2 text-primary"
          @click="goOpportunity"
        >
          {{ item.organization_name }}
        </button>
      </div>
    </div>

    <div
      v-if="loading"
      class="text-center py-8 text-medium-emphasis"
    >
      در حال بارگذاری...
    </div>

    <template v-else-if="item">
      <VRow dense>
        <VCol
          cols="12"
          md="6"
        >
          <VCard class="pa-4 mb-4">
            <div class="text-caption text-medium-emphasis mb-1">
              گام جاری
            </div>
            <template v-if="currentStep">
              <div class="text-h6 mb-1">
                {{ currentStep.title }}
              </div>
              <div class="text-body-2 text-medium-emphasis mb-4">
                مهلت: {{ formatJalaliFa(currentStep.due_on) || '—' }}
              </div>
              <VBtn
                color="primary"
                size="small"
                @click="openClose"
              >
                بستن گام
              </VBtn>
            </template>
            <div
              v-else
              class="text-body-2 text-medium-emphasis"
            >
              گام بازی تعریف نشده است.
            </div>
          </VCard>

          <VCard class="pa-4">
            <div class="text-caption text-medium-emphasis mb-2">
              وضعیت فرصت
            </div>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <VChip
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ toFaDigits(item.code) }}
              </VChip>
              <VChip
                size="small"
                color="success"
                variant="tonal"
              >
                {{ statusLabel[item.status] || item.status }}
              </VChip>
              <VChip
                v-if="item.stage?.name"
                size="small"
                variant="tonal"
              >
                {{ item.stage.name }}
              </VChip>
            </div>
          </VCard>
        </VCol>

        <VCol
          cols="12"
          md="6"
        >
          <VCard class="pa-4">
            <div class="text-subtitle-1 font-weight-medium mb-3">
              گام‌های قبلی
            </div>
            <VTimeline
              v-if="closedSteps.length"
              density="compact"
              side="end"
            >
              <VTimelineItem
                v-for="s in closedSteps"
                :key="s.id"
                size="small"
                dot-color="primary"
              >
                <div class="font-weight-medium">
                  {{ s.title }}
                </div>
                <div class="text-body-2">
                  {{ s.result }}
                </div>
                <div
                  v-if="s.due_on"
                  class="text-caption text-medium-emphasis"
                >
                  {{ formatJalaliFa(s.due_on) }}
                </div>
              </VTimelineItem>
            </VTimeline>
            <div
              v-else
              class="text-body-2 text-medium-emphasis"
            >
              هنوز گامی بسته نشده
            </div>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <FormDrawer
      v-model="closeOpen"
      title="بستن گام"
      subtitle="نتیجه و گام بعدی را مشخص کنید"
      :dirty="dirty"
      :width="480"
    >
      <VForm
        ref="formRef"
        @submit.prevent="submitClose"
      >
        <VTextarea
          v-model="closeForm.result"
          label="نتیجه این گام"
          rows="3"
          class="mb-3"
          :rules="[rules.required('نتیجه')]"
        />
        <VSelect
          v-model="closeForm.stage_id"
          :items="stages"
          label="مرحله قیف"
          class="mb-3"
        />
        <VSelect
          v-model="closeForm.status"
          :items="[
            { title: 'فعال', value: 'active' },
            { title: 'در حال پرورش', value: 'nurturing' },
            { title: 'متوقف موقت', value: 'paused' },
            { title: 'بسته‌شده-موفق', value: 'won' },
            { title: 'بسته‌شده-ناموفق', value: 'lost' },
          ]"
          label="وضعیت فرصت"
          class="mb-3"
        />
        <VTextField
          v-model="closeForm.next_step_title"
          label="گام بعدی (اختیاری)"
          class="mb-3"
        />
        <JalaliDateField
          v-model="closeForm.next_step_due_on"
          label="مهلت گام بعدی"
        />
      </VForm>

      <template #actions>
        <VBtn
          variant="text"
          color="secondary"
          @click="closeOpen = false"
        >
          انصراف
        </VBtn>
        <VBtn
          color="primary"
          :loading="closing"
          @click="submitClose"
        >
          بستن گام
        </VBtn>
      </template>
    </FormDrawer>
  </div>
</template>

<style scoped>
.steps-page {
  font-family: 'Vazirmatn', Vazirmatn, Tahoma, sans-serif !important;
}

.link-btn {
  background: none;
  border: 0;
  padding: 0;
  cursor: pointer;
  font: inherit;
  color: rgb(var(--v-theme-primary));
}

.link-btn:hover {
  text-decoration: underline;
}
</style>
