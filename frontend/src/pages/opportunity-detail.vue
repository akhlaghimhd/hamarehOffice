<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import {
  closeOpportunityStep,
  fetchOpportunity,
  fetchOpportunityStages,
  getCompany,
} from '@/utils/api'
import { formatJalaliFa, jalaliInputToIso, toFaDigits } from '@/utils/fa'

const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))

const item = ref<any | null>(null)
const stages = ref<any[]>([])
const loading = ref(false)
const errorMessage = ref('')
const closeOpen = ref(false)
const closing = ref(false)

const closeForm = ref({
  result: '',
  stage_id: null as number | null,
  status: 'active',
  next_step_title: '',
  next_step_due_on_jalali: '',
})

const statusLabel: Record<string, string> = {
  active: 'فعال',
  nurturing: 'در حال پرورش',
  paused: 'متوقف موقت',
  won: 'بسته‌شده-موفق',
  lost: 'بسته‌شده-ناموفق',
}

const productList = computed(() => {
  if (!item.value)
    return [] as string[]
  if (Array.isArray(item.value.products) && item.value.products.length)
    return item.value.products
  if (item.value.product_name)
    return [item.value.product_name]
  return []
})

async function load() {
  if (!getCompany()?.id) {
    errorMessage.value = 'شرکت جاری مشخص نشده است.'
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
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'خطا'
  }
  finally {
    loading.value = false
  }
}

async function submitClose() {
  closing.value = true
  errorMessage.value = ''
  try {
    const res = await closeOpportunityStep(id.value, {
      result: closeForm.value.result,
      stage_id: closeForm.value.stage_id,
      status: closeForm.value.status,
      next_step_title: closeForm.value.next_step_title || null,
      next_step_due_on: jalaliInputToIso(closeForm.value.next_step_due_on_jalali),
    })
    item.value = res.data
    closeOpen.value = false
    closeForm.value.result = ''
    closeForm.value.next_step_title = ''
    closeForm.value.next_step_due_on_jalali = ''
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'بستن گام ناموفق'
  }
  finally {
    closing.value = false
  }
}

onMounted(load)
</script>

<template>
  <div v-if="item">
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-6">
      <div>
        <VBtn
          variant="text"
          size="small"
          class="mb-2"
          @click="router.push('/opportunities')"
        >
          بازگشت به فرصت‌ها
        </VBtn>
        <h4 class="text-h4 mb-1">
          {{ item.organization_name }}
        </h4>
        <div class="d-flex flex-wrap gap-2">
          <VChip size="small">
            {{ toFaDigits(item.code) }}
          </VChip>
          <VChip
            size="small"
            color="primary"
            variant="tonal"
          >
            {{ statusLabel[item.status] || item.status }}
          </VChip>
          <VChip
            size="small"
            variant="tonal"
          >
            {{ item.stage?.name }}
          </VChip>
          <VChip
            v-for="p in productList"
            :key="p"
            size="small"
            variant="outlined"
            color="primary"
          >
            {{ p }}
          </VChip>
        </div>
      </div>
    </div>

    <VAlert
      v-if="errorMessage"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      {{ errorMessage }}
    </VAlert>

    <VRow>
      <VCol
        cols="12"
        md="4"
      >
        <VCard
          class="mb-4"
          title="مخاطب"
        >
          <VCardText>
            <div class="text-h6">
              {{ item.contact_name || '—' }}
            </div>
            <div class="text-body-2 text-medium-emphasis">
              {{ item.contact_role || '' }}
            </div>
            <div class="mt-2">
              {{ toFaDigits(item.contact_mobile || item.contact_phone || '—') }}
            </div>
          </VCardText>
        </VCard>

        <VCard title="جزئیات فرصت">
          <VCardText>
            <div class="mb-3">
              <div class="text-medium-emphasis mb-1">
                محصول‌ها
              </div>
              <div class="d-flex flex-wrap gap-1">
                <VChip
                  v-for="p in productList"
                  :key="p"
                  size="small"
                  variant="tonal"
                >
                  {{ p }}
                </VChip>
                <span v-if="!productList.length">—</span>
              </div>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="text-medium-emphasis">مرحله قیف</span><span>{{ item.stage?.name || '—' }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="text-medium-emphasis">وضعیت</span><span>{{ statusLabel[item.status] || item.status }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="text-medium-emphasis">مالک</span><span>{{ item.owner?.name || '—' }}</span>
            </div>
            <div class="d-flex justify-space-between">
              <span class="text-medium-emphasis">ارجاع به</span><span>{{ item.assignee?.name || '—' }}</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="8"
      >
        <VCard class="mb-4">
          <VCardItem>
            <VCardTitle>گام جاری</VCardTitle>
          </VCardItem>
          <VCardText>
            <template v-if="item.current_step || item.next_step">
              <div class="text-h6 mb-2">
                {{ item.current_step?.title || item.next_step }}
              </div>
              <div class="text-body-2 text-medium-emphasis mb-4">
                مهلت: {{ formatJalaliFa(item.current_step?.due_on || item.next_step_due_on) }}
              </div>
              <VBtn
                color="primary"
                @click="closeOpen = true"
              >
                بستن گام
              </VBtn>
            </template>
            <div
              v-else
              class="text-medium-emphasis"
            >
              گام بازی تعریف نشده است.
            </div>
          </VCardText>
        </VCard>

        <VCard title="گام‌های قبلی">
          <VCardText>
            <VTimeline
              density="compact"
              side="end"
            >
              <VTimelineItem
                v-for="s in (item.steps || []).filter((x: any) => x.status === 'closed')"
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
              v-if="!(item.steps || []).some((x: any) => x.status === 'closed')"
              class="text-medium-emphasis"
            >
              هنوز گامی بسته نشده
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VDialog
      v-model="closeOpen"
      max-width="560"
    >
      <VCard title="بستن گام">
        <VCardText>
          <VTextarea
            v-model="closeForm.result"
            label="نتیجه این گام *"
            rows="3"
            class="mb-3"
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
          <VTextField
            v-model="closeForm.next_step_due_on_jalali"
            label="مهلت گام بعدی (شمسی)"
            placeholder="۱۴۰۴/۰۹/۱۵"
            hint="سال/ماه/روز شمسی"
            persistent-hint
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            variant="text"
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
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
  <div v-else-if="loading">
    در حال بارگذاری...
  </div>
</template>
