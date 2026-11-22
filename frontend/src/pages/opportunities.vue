<script setup lang="ts">
import { useRouter } from 'vue-router'
import {
  createOpportunity,
  fetchOpportunities,
  fetchOpportunityStages,
  getCompany,
} from '@/utils/api'

const router = useRouter()
const items = ref<any[]>([])
const stages = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const quickOpen = ref(false)

const filters = ref({
  status: '',
  stage_id: '',
  q: '',
})

const form = ref({
  organization_name: '',
  product_name: 'نوتوپیا',
  contact_name: '',
  contact_mobile: '',
  contact_phone: '',
  next_step: '',
  next_step_due_on: '',
})

const statusItems = [
  { title: 'همه وضعیت‌ها', value: '' },
  { title: 'فعال', value: 'active' },
  { title: 'در حال پرورش', value: 'nurturing' },
  { title: 'متوقف موقت', value: 'paused' },
  { title: 'بسته‌شده-موفق', value: 'won' },
  { title: 'بسته‌شده-ناموفق', value: 'lost' },
]

const statusLabel: Record<string, string> = {
  active: 'فعال',
  nurturing: 'در حال پرورش',
  paused: 'متوقف موقت',
  won: 'بسته‌شده-موفق',
  lost: 'بسته‌شده-ناموفق',
}

const productItems = ['نوتوپیا', 'سایر']

async function load() {
  if (!getCompany()?.id) {
    errorMessage.value = 'شرکت جاری مشخص نشده است.'
    return
  }
  loading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams()
    if (filters.value.status) params.set('status', filters.value.status)
    if (filters.value.stage_id) params.set('stage_id', String(filters.value.stage_id))
    if (filters.value.q) params.set('q', filters.value.q)

    const [oppRes, stageRes] = await Promise.all([
      fetchOpportunities(params.toString()),
      fetchOpportunityStages(),
    ])
    items.value = oppRes.data || []
    stages.value = [
      { title: 'همه مراحل', value: '' },
      ...(stageRes.data || []).map((s: any) => ({ title: s.name, value: s.id })),
    ]
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'خطا در دریافت'
  }
  finally {
    loading.value = false
  }
}

async function saveQuick() {
  saving.value = true
  errorMessage.value = ''
  try {
    const res = await createOpportunity({ ...form.value })
    quickOpen.value = false
    form.value = {
      organization_name: '',
      product_name: 'نوتوپیا',
      contact_name: '',
      contact_mobile: '',
      contact_phone: '',
      next_step: '',
      next_step_due_on: '',
    }
    await load()
    if (res.data?.id)
      router.push(`/opportunities/${res.data.id}`)
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'ثبت ناموفق'
  }
  finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">فرصت‌ها</h4>
        <p class="text-body-1 text-medium-emphasis mb-0">
          همه فرصت‌ها · {{ items.length }} مورد
        </p>
      </div>
      <div class="d-flex gap-2">
        <VBtn variant="tonal" @click="quickOpen = true">ثبت سریع</VBtn>
        <VBtn color="primary" prepend-icon="ri-add-line" @click="quickOpen = true">
          فرصت
        </VBtn>
      </div>
    </div>

    <VCard class="mb-4">
      <VCardText class="d-flex flex-wrap gap-3">
        <VTextField
          v-model="filters.q"
          density="compact"
          hide-details
          label="جستجوی سازمان، کد، مخاطب..."
          style="min-inline-size: 16rem;"
          @keyup.enter="load"
        />
        <VSelect
          v-model="filters.status"
          :items="statusItems"
          density="compact"
          hide-details
          style="min-inline-size: 11rem;"
          @update:model-value="load"
        />
        <VSelect
          v-model="filters.stage_id"
          :items="stages"
          density="compact"
          hide-details
          style="min-inline-size: 12rem;"
          @update:model-value="load"
        />
        <VBtn variant="text" @click="load">اعمال</VBtn>
      </VCardText>
    </VCard>

    <VAlert v-if="errorMessage" type="error" variant="tonal" class="mb-4">{{ errorMessage }}</VAlert>

    <VCard>
      <VDataTable
        :headers="[
          { title: 'کد', key: 'code' },
          { title: 'سازمان', key: 'organization_name' },
          { title: 'محصول', key: 'product_name' },
          { title: 'وضعیت', key: 'status' },
          { title: 'مرحله', key: 'stage.name' },
          { title: 'گام بعدی', key: 'next_step' },
          { title: 'تاریخ گام', key: 'next_step_due_on' },
        ]"
        :items="items"
        :loading="loading"
        class="text-no-wrap"
        @click:row="(_: any, ctx: any) => $router.push(`/opportunities/${ctx.item.id}`)"
      >
        <template #item.status="{ item }">
          <VChip size="small" variant="tonal">{{ statusLabel[item.status] || item.status }}</VChip>
        </template>
        <template #no-data>فرصتی ثبت نشده</template>
        <template #bottom />
      </VDataTable>
    </VCard>

    <VDialog v-model="quickOpen" max-width="560">
      <VCard title="ثبت سریع فرصت">
        <VCardText>
          <p class="text-body-2 text-medium-emphasis mb-4">
            حداقل اطلاعات برای شروع پیگیری — بعداً از ویرایش فرصت تکمیل کنید.
          </p>
          <VRow>
            <VCol cols="12">
              <VTextField v-model="form.organization_name" label="سازمان *" />
            </VCol>
            <VCol cols="12">
              <VSelect v-model="form.product_name" :items="productItems" label="محصول" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.contact_name" label="فرد تماس" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.contact_mobile" label="موبایل" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.contact_phone" label="تلفن" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.next_step_due_on" type="date" label="تاریخ گام بعدی" />
            </VCol>
            <VCol cols="12">
              <VTextarea v-model="form.next_step" label="گام بعدی *" rows="2" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="quickOpen = false">انصراف</VBtn>
          <VBtn color="primary" :loading="saving" @click="saveQuick">ثبت و مشاهده</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
