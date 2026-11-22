<script setup lang="ts">
import FormDrawer from '@/components/forms/FormDrawer.vue'
import PhoneField from '@/components/forms/PhoneField.vue'
import { useNotify } from '@/composables/useNotify'
import { createParty, fetchParties, getCompany } from '@/utils/api'
import { toFaDigits } from '@/utils/fa'
import { rules } from '@/utils/formRules'
import trophy from '@images/misc/trophy.png'

const { success, error: notifyError } = useNotify()

const items = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const drawerOpen = ref(false)
const formRef = ref()
const dirty = ref(false)

const emptyForm = () => ({
  type: 'organization',
  name: '',
  role: 'customer',
  phone: '',
  email: '',
})

const form = ref(emptyForm())
watch(form, () => { dirty.value = true }, { deep: true })

function openCreate() {
  form.value = emptyForm()
  dirty.value = false
  drawerOpen.value = true
}

async function load() {
  if (!getCompany()?.id) {
    notifyError('شرکت جاری مشخص نشده است. از هدر یک شرکت انتخاب کنید.')
    return
  }

  loading.value = true
  try {
    const res = await fetchParties()
    items.value = res.data || []
  }
  catch (e: any) {
    notifyError(e?.message || 'خطا در دریافت')
  }
  finally {
    loading.value = false
  }
}

async function save() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid)
    return

  if (!getCompany()?.id) {
    notifyError('شرکت جاری مشخص نشده است.')
    return
  }
  saving.value = true
  try {
    await createParty({ ...form.value })
    dirty.value = false
    drawerOpen.value = false
    success('طرف تجاری ثبت شد')
    await load()
  }
  catch (e: any) {
    notifyError(e?.message || 'ثبت ناموفق')
  }
  finally {
    saving.value = false
  }
}

onMounted(load)

const roleLabel: Record<string, string> = {
  customer: 'مشتری',
  supplier: 'تأمین‌کننده',
  contractor: 'پیمانکار',
  partner: 'شریک',
  sales_representative: 'نماینده',
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-4 mb-md-6">
      <div>
        <h4 class="text-h4 mb-1">
          طرف‌های تجاری
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          مشتری، تأمین‌کننده، پیمانکار · {{ toFaDigits(items.length) }} مورد
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="ri-user-add-line"
        @click="openCreate"
      >
        طرف تجاری جدید
      </VBtn>
    </div>

    <!-- دسکتاپ: جدول -->
    <VCard class="d-none d-md-block">
      <VDataTable
        :headers="[
          { title: 'نام', key: 'party.name' },
          { title: 'نوع', key: 'party.type' },
          { title: 'نقش', key: 'role' },
          { title: 'تلفن', key: 'party.phone' },
          { title: 'ایمیل', key: 'party.email' },
        ]"
        :items="items"
        :loading="loading"
        class="text-no-wrap"
      >
        <template #item.party.type="{ item }">
          {{ item.party?.type === 'person' ? 'حقیقی' : 'حقوقی' }}
        </template>
        <template #item.role="{ item }">
          <VChip
            size="small"
            color="primary"
            variant="tonal"
          >
            {{ roleLabel[item.role] || item.role }}
          </VChip>
        </template>
        <template #item.party.phone="{ item }">
          {{ toFaDigits(item.party?.phone || '—') }}
        </template>
        <template #no-data>
          داده‌ای نیست
        </template>
        <template #bottom />
      </VDataTable>
    </VCard>

    <!-- موبایل: کارت -->
    <div class="d-md-none">
      <div
        v-if="loading"
        class="text-center py-8 text-medium-emphasis"
      >
        در حال بارگذاری...
      </div>
      <div
        v-else-if="!items.length"
        class="text-center py-8 text-medium-emphasis"
      >
        داده‌ای نیست
      </div>
      <VCard
        v-for="item in items"
        :key="item.id"
        class="mb-3"
      >
        <VCardText class="pa-4">
          <div class="d-flex align-start justify-space-between gap-2 mb-2">
            <div>
              <div class="text-subtitle-1 font-weight-medium">
                {{ item.party?.name }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ item.party?.type === 'person' ? 'حقیقی' : 'حقوقی' }}
              </div>
            </div>
            <VChip
              size="small"
              color="primary"
              variant="tonal"
            >
              {{ roleLabel[item.role] || item.role }}
            </VChip>
          </div>
          <div class="text-body-2">
            <span class="text-medium-emphasis">تلفن: </span>{{ toFaDigits(item.party?.phone || '—') }}
          </div>
          <div
            v-if="item.party?.email"
            class="text-body-2"
          >
            <span class="text-medium-emphasis">ایمیل: </span>{{ item.party.email }}
          </div>
        </VCardText>
      </VCard>
    </div>

    <FormDrawer
      v-model="drawerOpen"
      title="ثبت طرف تجاری"
      subtitle="مشتری، تأمین‌کننده یا نقش‌های دیگر"
      :dirty="dirty"
      :illustration="trophy"
      :width="480"
    >
      <VForm
        ref="formRef"
        @submit.prevent="save"
      >
        <VRow>
          <VCol cols="12" sm="6">
            <VSelect
              v-model="form.type"
              :items="[
                { title: 'حقوقی', value: 'organization' },
                { title: 'حقیقی', value: 'person' },
              ]"
              label="نوع"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VSelect
              v-model="form.role"
              :items="[
                { title: 'مشتری', value: 'customer' },
                { title: 'تأمین‌کننده', value: 'supplier' },
                { title: 'پیمانکار', value: 'contractor' },
                { title: 'شریک', value: 'partner' },
                { title: 'نماینده', value: 'sales_representative' },
              ]"
              label="نقش تجاری"
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="form.name"
              label="نام"
              :rules="[rules.required('نام')]"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <PhoneField
              v-model="form.phone"
              label="تلفن"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="form.email"
              label="ایمیل"
              type="email"
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
          @click="save"
        >
          ثبت
        </VBtn>
      </template>
    </FormDrawer>
  </div>
</template>
