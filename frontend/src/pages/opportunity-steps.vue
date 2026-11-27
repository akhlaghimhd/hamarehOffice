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

const typeLabel: Record<string, string> = {
  call: 'تماس',
  meeting: 'جلسه',
  message: 'ایمیل / پیام',
  visit: 'بازدید',
  proposal: 'ارسال پیشنهاد',
  other: 'سایر',
  note: 'یادداشت',
  referral: 'ارجاع',
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

const stepActivities = computed(() => {
  const d = item.value
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

const productList = computed(() => {
  if (!item.value)
    return [] as string[]
  if (Array.isArray(item.value.products) && item.value.products.length)
    return item.value.products
  if (item.value.product_name)
    return [item.value.product_name]
  return []
})

const referrals = computed(() => {
  if (Array.isArray(item.value?.referrals))
    return item.value.referrals
  return [] as any[]
})

const knowledgeItems = computed(() => {
  if (Array.isArray(item.value?.knowledge_guides))
    return item.value.knowledge_guides
  // نمونه ساختار راهنما تا API کامل شود
  return [
    { title: 'فصل ۴ – جایگاه‌یابی و معماری پیام', meta: 'رسمی · فصل' },
    { title: 'فرآیند فروش و معیار عبور مراحل', meta: 'رسمی · کارت' },
  ] as any[]
})

function activitiesOfStep(stepId: number | null | undefined) {
  if (!item.value?.activities?.length)
    return [] as any[]
  if (!stepId)
    return [] as any[]
  return item.value.activities.filter((a: any) => a.step_id === stepId)
}

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

function goList() {
  router.push('/opportunities')
}

function goActivityNew() {
  router.push(`/opportunities/${id.value}/activities/new?from=steps`)
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
  <div class="steps-workspace">
    <div
      v-if="loading"
      class="text-center py-10 text-medium-emphasis"
    >
      در حال بارگذاری...
    </div>

    <template v-else-if="item">
      <!-- Breadcrumb + actions -->
      <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-2">
        <div class="d-flex flex-wrap align-center gap-1 text-caption text-medium-emphasis">
          <button
            type="button"
            class="crumb-btn"
            @click="goList"
          >
            خانه
          </button>
          <VIcon
            icon="ri-arrow-left-s-line"
            size="14"
          />
          <button
            type="button"
            class="crumb-btn"
            @click="goList"
          >
            فرصت‌ها
          </button>
          <VIcon
            icon="ri-arrow-left-s-line"
            size="14"
          />
          <span class="text-truncate">{{ item.organization_name }}</span>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <VBtn
            variant="outlined"
            color="secondary"
            size="small"
            prepend-icon="ri-share-forward-line"
          >
            پیگیری ارجاع
          </VBtn>
          <VBtn
            color="success"
            size="small"
            prepend-icon="ri-checkbox-circle-line"
          >
            بستن فرصت
          </VBtn>
        </div>
      </div>

      <!-- Title row -->
      <div class="d-flex flex-wrap align-start justify-space-between gap-3 mb-4">
        <div class="min-w-0">
          <div class="d-flex align-center gap-2 mb-2">
            <h1 class="text-h5 font-weight-medium mb-0 text-wrap">
              {{ item.organization_name }}
            </h1>
            <VBtn
              icon
              variant="text"
              size="x-small"
              color="secondary"
              :title="'ویرایش'"
            >
              <VIcon
                icon="ri-pencil-line"
                size="18"
              />
            </VBtn>
          </div>
          <div class="d-flex flex-wrap gap-1">
            <VChip
              size="x-small"
              color="primary"
              variant="tonal"
            >
              {{ toFaDigits(item.code) }}
            </VChip>
            <VChip
              size="x-small"
              color="success"
              variant="tonal"
            >
              {{ statusLabel[item.status] || item.status }}
            </VChip>
            <VChip
              v-for="p in productList"
              :key="p"
              size="x-small"
              variant="tonal"
            >
              {{ p }}
            </VChip>
            <VChip
              v-if="item.stage?.name"
              size="x-small"
              variant="tonal"
              color="secondary"
            >
              {{ item.stage.name }}
            </VChip>
            <VChip
              v-if="item.assignee?.name"
              size="x-small"
              variant="outlined"
            >
              ارجاع به {{ item.assignee.name }}
            </VChip>
          </div>
        </div>
      </div>

      <VRow dense>
        <!-- LEFT -->
        <VCol
          cols="12"
          lg="4"
          order="2"
          order-lg="1"
        >
          <!-- مخاطب -->
          <VCard class="mb-3 side-card">
            <VCardText class="pa-4">
              <div class="text-caption text-medium-emphasis mb-1">
                مخاطب
              </div>
              <div class="text-subtitle-1 font-weight-medium">
                {{ item.contact_name || '—' }}
              </div>
              <div
                v-if="item.contact_role"
                class="text-body-2 text-medium-emphasis"
              >
                {{ item.contact_role }}
              </div>
              <div class="text-body-2 mt-1">
                {{ toFaDigits(item.contact_mobile || item.contact_phone || '—') }}
              </div>
            </VCardText>
          </VCard>

          <!-- جزئیات فرصت -->
          <VCard class="mb-3 side-card">
            <VCardText class="pa-0">
              <div class="px-4 pt-3 pb-2 text-body-2 font-weight-medium">
                جزئیات فرصت
              </div>
              <VDivider />
              <div class="detail-row">
                <span class="label">محصول</span>
                <span class="value">{{ productList.join('، ') || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">مرحله قیف</span>
                <span class="value">{{ item.stage?.name || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">وضعیت</span>
                <span class="value">{{ statusLabel[item.status] || item.status }}</span>
              </div>
              <div class="detail-row">
                <span class="label">کاربرد</span>
                <span class="value">{{ item.application || item.usage || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">تیراژ</span>
                <span class="value">{{ toFaDigits(item.quantity || item.tirazh || '—') }}</span>
              </div>
              <div class="detail-row">
                <span class="label">بودجه</span>
                <span class="value">{{ item.budget_label || item.budget || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">مالک</span>
                <span class="value">{{ item.owner?.name || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">ارجاع به</span>
                <span class="value">{{ item.assignee?.name || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">آخرین تعامل</span>
                <span class="value">{{ formatJalaliFa(item.last_activity_at || item.updated_at) || '—' }}</span>
              </div>
              <div class="detail-row detail-row--block">
                <span class="label">خلاصه</span>
                <span class="value text-wrap">{{ item.summary || item.need_summary || '—' }}</span>
              </div>
            </VCardText>
          </VCard>

          <!-- راهنمای دانش -->
          <VCard class="mb-3 side-card">
            <VCardText class="pa-0">
              <div class="px-4 pt-3 pb-2 text-body-2 font-weight-medium">
                راهنمای دانش
              </div>
              <VDivider />
              <div
                v-for="(k, idx) in knowledgeItems"
                :key="idx"
                class="knowledge-row"
              >
                <div class="text-body-2">
                  {{ k.title }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ k.meta || k.type || '' }}
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- RIGHT -->
        <VCol
          cols="12"
          lg="8"
          order="1"
          order-lg="2"
        >
          <!-- گام جاری -->
          <VCard class="mb-3 main-card current-step-card">
            <VCardText class="pa-4">
              <div class="d-flex flex-wrap align-start justify-space-between gap-3">
                <div class="min-w-0 flex-grow-1">
                  <div class="text-caption text-medium-emphasis mb-1">
                    گام جاری
                  </div>
                  <template v-if="currentStep">
                    <div class="text-h6 font-weight-medium mb-2 text-wrap">
                      {{ currentStep.title }}
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-body-2 text-medium-emphasis">
                      <span>مهلت: {{ formatJalaliFa(currentStep.due_on) || '—' }}</span>
                      <span v-if="item.assignee?.name">{{ item.assignee.name }}</span>
                    </div>
                  </template>
                  <div
                    v-else
                    class="text-body-2 text-medium-emphasis"
                  >
                    گام بازی تعریف نشده است.
                  </div>
                </div>

                <div
                  v-if="currentStep"
                  class="d-flex flex-column align-end gap-2"
                >
                  <VBtn
                    color="primary"
                    size="small"
                    @click="openClose"
                  >
                    بستن گام
                  </VBtn>
                  <VBtn
                    variant="outlined"
                    color="error"
                    size="small"
                    disabled
                  >
                    حذف گام
                  </VBtn>
                  <div class="text-caption text-medium-emphasis text-end" style="max-inline-size: 12rem;">
                    گام را با نتیجه ببندید؛ فعالیت جداگانه از دکمه زیر است.
                  </div>
                </div>
              </div>
            </VCardText>
          </VCard>

          <!-- فعالیت‌های زیر این گام -->
          <VCard class="mb-3 main-card">
            <VCardText class="pa-4">
              <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-3">
                <div class="text-subtitle-2 font-weight-medium">
                  فعالیت‌های زیر این گام
                </div>
                <VBtn
                  color="primary"
                  size="small"
                  variant="tonal"
                  prepend-icon="ri-add-line"
                  @click="goActivityNew"
                >
                  ثبت فعالیت
                </VBtn>
              </div>

              <div
                v-if="!stepActivities.length"
                class="text-body-2 text-medium-emphasis pa-2"
              >
                هنوز فعالیتی برای این گام ثبت نشده.
              </div>

              <div
                v-for="a in stepActivities"
                :key="a.id"
                class="activity-item"
              >
                <div class="d-flex align-start justify-space-between gap-2">
                  <div class="min-w-0">
                    <div class="d-flex flex-wrap align-center gap-2 mb-1">
                      <span class="text-body-2 font-weight-medium">
                        {{ typeLabel[a.type] || a.subject || a.type }}
                      </span>
                      <span class="text-caption text-medium-emphasis">
                        {{ formatJalaliFa(a.happened_at || a.created_at) }}
                      </span>
                    </div>
                    <div class="text-body-2 text-medium-emphasis text-wrap">
                      {{ a.body }}
                    </div>
                    <div
                      v-if="a.created_by?.name || a.user?.name"
                      class="text-caption text-medium-emphasis mt-1"
                    >
                      ثبت: {{ a.created_by?.name || a.user?.name }}
                    </div>
                  </div>
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="error"
                    disabled
                  >
                    حذف
                  </VBtn>
                </div>
              </div>
            </VCardText>
          </VCard>

          <!-- گام‌های قبلی -->
          <VCard class="mb-3 main-card">
            <VCardText class="pa-4">
              <div class="text-subtitle-2 font-weight-medium mb-3">
                گام‌های قبلی
              </div>

              <div
                v-if="!closedSteps.length"
                class="text-body-2 text-medium-emphasis"
              >
                هنوز گامی بسته نشده.
              </div>

              <VExpansionPanels
                v-else
                variant="accordion"
                class="step-panels"
              >
                <VExpansionPanel
                  v-for="s in closedSteps"
                  :key="s.id"
                >
                  <VExpansionPanelTitle>
                    <div class="d-flex flex-wrap align-center justify-space-between gap-2 w-100 pe-2">
                      <span class="font-weight-medium">{{ s.title }}</span>
                      <div class="d-flex align-center gap-2">
                        <span class="text-caption text-medium-emphasis">
                          {{ formatJalaliFa(s.closed_at || s.due_on) }}
                        </span>
                        <VChip
                          size="x-small"
                          variant="tonal"
                        >
                          {{ toFaDigits(activitiesOfStep(s.id).length) }} فعالیت
                        </VChip>
                      </div>
                    </div>
                  </VExpansionPanelTitle>
                  <VExpansionPanelText>
                    <div
                      v-if="s.result"
                      class="text-body-2 mb-2"
                    >
                      <span class="text-medium-emphasis">نتیجه: </span>{{ s.result }}
                    </div>
                    <div
                      v-for="a in activitiesOfStep(s.id)"
                      :key="a.id"
                      class="activity-item activity-item--nested"
                    >
                      <div class="text-body-2 font-weight-medium">
                        {{ typeLabel[a.type] || a.subject || a.type }}
                        <span class="text-caption text-medium-emphasis ms-2">
                          {{ formatJalaliFa(a.happened_at || a.created_at) }}
                        </span>
                      </div>
                      <div class="text-body-2 text-medium-emphasis">
                        {{ a.body }}
                      </div>
                    </div>
                  </VExpansionPanelText>
                </VExpansionPanel>
              </VExpansionPanels>
            </VCardText>
          </VCard>

          <!-- سلسله ارجاع -->
          <VCard class="main-card">
            <VCardText class="pa-4">
              <div class="text-subtitle-2 font-weight-medium mb-3">
                سلسله ارجاع
              </div>
              <VTable
                v-if="referrals.length"
                density="compact"
                class="referral-table"
              >
                <thead>
                  <tr>
                    <th>#</th>
                    <th>زمان</th>
                    <th>از</th>
                    <th>به</th>
                    <th>عمل</th>
                    <th>توضیح</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(r, idx) in referrals"
                    :key="r.id || idx"
                  >
                    <td>{{ toFaDigits(idx + 1) }}</td>
                    <td>{{ formatJalaliFa(r.created_at) }}</td>
                    <td>{{ r.from_user?.name || r.from || '—' }}</td>
                    <td>{{ r.to_user?.name || r.to || '—' }}</td>
                    <td>{{ r.action || 'ارجاع' }}</td>
                    <td>{{ r.note || r.description || '—' }}</td>
                  </tr>
                </tbody>
              </VTable>
              <div
                v-else
                class="text-body-2 text-medium-emphasis"
              >
                ارجاعی ثبت نشده است.
              </div>
            </VCardText>
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
.steps-workspace {
  font-family: 'Vazirmatn', Vazirmatn, Tahoma, sans-serif !important;
}

.steps-workspace :deep(*) {
  font-family: 'Vazirmatn', Vazirmatn, Tahoma, sans-serif !important;
}

.crumb-btn {
  background: none;
  border: 0;
  padding: 0;
  cursor: pointer;
  font: inherit;
  color: inherit;
}

.crumb-btn:hover {
  color: rgb(var(--v-theme-primary));
}

.side-card,
.main-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  box-shadow: none !important;
}

.current-step-card {
  border-color: rgba(var(--v-theme-primary), 0.35);
}

.detail-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.65rem 1rem;
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  font-size: 0.875rem;
}

.detail-row--block {
  flex-direction: column;
  gap: 0.25rem;
}

.detail-row .label {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  flex-shrink: 0;
}

.detail-row .value {
  text-align: end;
}

.detail-row--block .value {
  text-align: start;
}

.knowledge-row {
  padding: 0.75rem 1rem;
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.activity-item {
  padding: 0.85rem 0.75rem;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 10px;
  margin-block-end: 0.75rem;
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.activity-item--nested {
  margin-block-start: 0.5rem;
}

.step-panels :deep(.v-expansion-panel-title) {
  padding-inline: 0.75rem;
  min-block-size: 48px;
}

.referral-table :deep(th),
.referral-table :deep(td) {
  white-space: nowrap;
  font-size: 0.8125rem;
}
</style>
