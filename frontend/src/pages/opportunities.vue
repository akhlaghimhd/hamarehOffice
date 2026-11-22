<script setup lang="ts">
import { useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import PhoneField from '@/components/forms/PhoneField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  createOpportunity,
  fetchOpportunities,
  fetchOpportunityStages,
  getCompany,
} from '@/utils/api'
import { formatJalaliFa, toFaDigits } from '@/utils/fa'
import { rules } from '@/utils/formRules'
import trophy from '@images/misc/trophy.png'

const router = useRouter()
const { success, error: notifyError } = useNotify()

const items = ref<any[]>([])
const stages = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const drawerOpen = ref(false)
const formRef = ref()
const dirty = ref(false)

const filters = ref({
  status: '',
  stage_id: '',
  q: '',
})

const emptyForm = () => ({
  organization_name: '',
  products: ['نوتوپیا'] as string[],
  contact_name: '',
  contact_mobile: '',
  contact_phone: '',
  next_step: '',
  next_step_due_on: null as string | null,
})

const form = ref(emptyForm())

watch(form, () => { dirty.value = true }, { deep: true })

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

const productItems = ['نوتوپیا', 'سایر محصولات چاپی', 'خدمات سفارشی']

function openCreate() {
  form.value = emptyForm()
  dirty.value = false
  drawerOpen.value = true
}

function goToOpportunity(_event: unknown, ctx: { item: { id: number } }) {
  router.push('/opportunities/' + ctx.item.id)
}

async function load() {
  if (!getCompany()?.id) {
    notifyError('شرکت جاری مشخص نشده است.')
    return
  }
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.status)
      params.set('status', filters.value.status)
    if (filters.value.stage_id)
      params.set('stage_id', String(filters.value.stage_id))
    if (filters.value.q)
      params.set('q', filters.value.q)

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
    notifyError(e?.message || 'خطا در دریافت فرصت‌ها')
  }
  finally {
    loading.value = false
  }
}

async function saveQuick() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid)
    return

  if (!form.value.products?.length) {
    notifyError('حداقل یک محصول را انتخاب کنید.')
    return
  }

  saving.value = true
  try {
    const res = await createOpportunity({
      organization_name: form.value.organization_name,
      products: form.value.products,
      contact_name: form.value.contact_name || null,
      contact_mobile: form.value.contact_mobile || null,
      contact_phone: form.value.contact_phone || null,
      next_step: form.value.next_step,
      next_step_due_on: form.value.next_step_due_on,
    })
    dirty.value = false
    drawerOpen.value = false
    success('فرصت با موفقیت ثبت شد')
    await load()
    if (res.data?.id)
      router.push('/opportunities/' + res.data.id)
  }
  catch (e: any) {
    notifyError(e?.message || 'ثبت فرصت ناموفق بود')
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
        <h4 class="text-h4 mb-1">
          فرصت‌ها
        </h4>
        <p class="text-body-1 text-medium-emphasis mb-0">
          همه فرصت‌ها · {{ toFaDigits(items.length) }} مورد
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="ri-add-line"
        @click="openCreate"
      >
        فرصت جدید
      </VBtn>
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
        <VBtn
          variant="tonal"
          color="secondary"
          @click="load"
        >
          اعمال فیلتر
        </VBtn>
      </VCardText>
    </VCard>

    <VCard>
      <VDataTable
        :headers="[
          { title: 'کد', key: 'code' },
          { title: 'سازمان', key: 'organization_name' },
          { title: 'محصول‌ها', key: 'products' },
          { title: 'وضعیت', key: 'status' },
          { title: 'مرحله', key: 'stage.name' },
          { title: 'گام بعدی', key: 'next_step' },
          { title: 'تاریخ گام', key: 'next_step_due_on' },
        ]"
        :items="items"
        :loading="loading"
        class="text-no-wrap"
        @click:row="goToOpportunity"
      >
        <template #item.code="{ item }">
          {{ toFaDigits(item.code) }}
        </template>
        <template #item.products="{ item }">
          <div class="d-flex flex-wrap gap-1 py-1">
            <VChip
              v-for="p in (item.products?.length ? item.products : (item.product_name ? [item.product_name] : []))"
              :key="p"
              size="small"
              variant="tonal"
              color="primary"
            >
              {{ p }}
            </VChip>
            <span v-if="!(item.products?.length || item.product_name)">—</span>
          </div>
        </template>
        <template #item.status="{ item }">
          <VChip
            size="small"
            variant="tonal"
          >
            {{ statusLabel[item.status] || item.status }}
          </VChip>
        </template>
        <template #item.next_step_due_on="{ item }">
          {{ formatJalaliFa(item.next_step_due_on) }}
        </template>
        <template #no-data>
          فرصتی ثبت نشده
        </template>
        <template #bottom />
      </VDataTable>
    </VCard>

    <FormDrawer
      v-model="drawerOpen"
      title="ثبت سریع فرصت"
      subtitle="حداقل اطلاعات برای شروع پیگیری"
      :dirty="dirty"
      :illustration="trophy"
      :width="520"
    >
      <VForm
        ref="formRef"
        @submit.prevent="saveQuick"
      >
        <VRow>
          <VCol cols="12">
            <VTextField
              v-model="form.organization_name"
              label="سازمان"
              :rules="[rules.required('سازمان')]"
            />
          </VCol>
          <VCol cols="12">
            <VSelect
              v-model="form.products"
              :items="productItems"
              label="محصول‌ها"
              multiple
              chips
              closable-chips
              :rules="[rules.required('محصول')]"
              hint="یک یا چند محصول"
              persistent-hint
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="form.contact_name"
              label="فرد تماس"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <PhoneField
              v-model="form.contact_mobile"
              label="موبایل"
              mobile
            />
          </VCol>
          <VCol cols="12" sm="6">
            <PhoneField
              v-model="form.contact_phone"
              label="تلفن"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <JalaliDateField
              v-model="form.next_step_due_on"
              label="تاریخ گام بعدی"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea
              v-model="form.next_step"
              label="گام بعدی"
              rows="2"
              :rules="[rules.required('گام بعدی')]"
            />
          </VCol>
        </VRow>
      </VForm>

      <template #actions>
        <VBtn
          variant="text"
          color="secondary"
          @click="drawerOpen = false"
        >
          انصراف
        </VBtn>
        <VBtn
          color="primary"
          :loading="saving"
          @click="saveQuick"
        >
          ثبت و مشاهده
        </VBtn>
      </template>
    </FormDrawer>
  </div>
</template>
