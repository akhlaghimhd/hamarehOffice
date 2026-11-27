<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  assignOpportunity,
  closeOpportunityStep,
  fetchCompanyUsers,
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
const assignOpen = ref(false)
const actionSaving = ref(false)
const assignFormRef = ref()
const companyUsers = ref<{ title: string; value: number }[]>([])
const assignForm = ref({
  to_user_id: null as number | null,
  note: '',
})

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

const productList = computed(() => {
  if (!item.value) return [] as string[]
  if (Array.isArray(item.value.products) && item.value.products.length) return item.value.products
  if (item.value.product_name) return [item.value.product_name]
  return []
})

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
    notifyError(e?.message || 'خطا در بارگذاری فرصت')
  }
  finally {
    loading.value = false
  }
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
  if (!valid) return
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

async function openAssign() {
  assignForm.value = {
    to_user_id: item.value?.assignee?.id ?? null,
    note: '',
  }
  if (!companyUsers.value.length) {
    try {
      const res = await fetchCompanyUsers()
      companyUsers.value = (res.data || []).map((u: any) => ({ title: u.name, value: u.id }))
    }
    catch (e: any) {
      notifyError(e?.message || 'خطا در دریافت کاربران')
      return
    }
  }
  assignOpen.value = true
}
async function submitAssign() {
  const { valid } = await assignFormRef.value?.validate?.() ?? { valid: true }
  if (!valid) return
  actionSaving.value = true
  try {
    await assignOpportunity(id.value, {
      to_user_id: assignForm.value.to_user_id,
      note: assignForm.value.note || null,
    })
    assignOpen.value = false
    success('ارجاع انجام شد')
    await load()
  }
  catch (e: any) {
    notifyError(e?.message || 'ارجاع ناموفق بود')
  }
  finally {
    actionSaving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div v-if="item">
    <div class="d-flex flex-wrap align-center gap-2 mb-4">
      <VBtn icon variant="text" @click="$router.push('/opportunities')">
        <VIcon icon="ri-arrow-right-s-line" />
      </VBtn>
      <h1 class="text-h5 mb-0">{{ item.organization_name }}</h1>
    </div>

    <VRow>
      <VCol cols="12" md="8">
        <VCard class="mb-4">
          <VCardText>
            <template v-if="item.current_step || item.next_step">
              <div class="text-h6 mb-2">
                {{ item.current_step?.title || item.next_step }}
              </div>
              <div class="text-body-2 text-medium-emphasis mb-4">
                مهلت: {{ formatJalaliFa(item.current_step?.due_on || item.next_step_due_on) }}
              </div>
              <div class="d-flex flex-wrap gap-2">
                <VBtn color="primary" @click="openClose">بستن گام</VBtn>
                <VBtn color="secondary" variant="tonal" prepend-icon="ri-user-add-line" @click="openAssign">ارجاع</VBtn>
                <VBtn color="primary" variant="tonal" :to="`/opportunities/${id}/steps`">مدیریت گام‌ها</VBtn>
                <VBtn color="primary" variant="text" :to="`/opportunities/${id}/activities/new?from=detail`">ثبت فعالیت</VBtn>
              </div>
            </template>
            <div v-else class="text-medium-emphasis">گام جاری تعریف نشده</div>
          </VCardText>
        </VCard>

        <VCard class="mb-4">
          <VCardTitle>اطلاعات فرصت</VCardTitle>
          <VCardText>
            <div class="mb-2"><span class="text-medium-emphasis">وضعیت:</span> {{ statusLabel[item.status] || item.status }}</div>
            <div class="mb-2"><span class="text-medium-emphasis">مرحله:</span> {{ item.stage?.name || '—' }}</div>
            <div class="mb-2"><span class="text-medium-emphasis">مسئول:</span> {{ item.assignee?.name || item.owner?.name || '—' }}</div>
            <div class="mb-2"><span class="text-medium-emphasis">مخاطب:</span> {{ item.contact_name || '—' }}</div>
            <div v-if="productList.length" class="mb-2">
              <span class="text-medium-emphasis">محصول:</span> {{ productList.join('، ') }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <FormDrawer v-model="closeOpen" title="بستن گام" subtitle="نتیجه و گام بعدی را مشخص کنید" :dirty="dirty" :width="480">
      <VForm ref="formRef" @submit.prevent="submitClose">
        <VTextarea v-model="closeForm.result" label="نتیجه این گام" rows="3" class="mb-3" :rules="[rules.required]" />
        <VSelect v-model="closeForm.stage_id" :items="stages" label="مرحله قیف" class="mb-3" />
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
        <VTextField v-model="closeForm.next_step_title" label="گام بعدی (اختیاری)" class="mb-3" />
        <JalaliDateField v-model="closeForm.next_step_due_on" label="مهلت گام بعدی" />
      </VForm>
      <template #actions>
        <VBtn variant="text" color="secondary" @click="closeOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="closing" @click="submitClose">بستن گام</VBtn>
      </template>
    </FormDrawer>

    <FormDrawer v-model="assignOpen" title="ارجاع فرصت" :subtitle="item?.organization_name || ''" :width="420">
      <VForm ref="assignFormRef" @submit.prevent="submitAssign">
        <VSelect v-model="assignForm.to_user_id" :items="companyUsers" label="ارجاع به" class="mb-3" :rules="[rules.required]" />
        <VTextarea v-model="assignForm.note" label="یادداشت" rows="3" />
      </VForm>
      <template #actions>
        <VBtn variant="text" color="secondary" @click="assignOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="actionSaving" @click="submitAssign">ارجاع</VBtn>
      </template>
    </FormDrawer>
  </div>
  <div v-else-if="loading">در حال بارگذاری...</div>
</template>
