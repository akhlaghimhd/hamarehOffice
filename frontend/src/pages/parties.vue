<script setup lang="ts">
import { createParty, fetchParties, getCompany } from '@/utils/api'

const items = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const dialog = ref(false)

const form = ref({
  type: 'organization',
  name: '',
  role: 'customer',
  phone: '',
  email: '',
})

async function load() {
  if (!getCompany()?.id) {
    errorMessage.value = 'شرکت جاری مشخص نشده است. از دکمه شرکت در هدر یک شرکت انتخاب کنید.'
    return
  }

  loading.value = true
  errorMessage.value = ''
  try {
    const res = await fetchParties()
    items.value = res.data || []
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'خطا در دریافت'
  }
  finally {
    loading.value = false
  }
}

async function save() {
  if (!getCompany()?.id) {
    errorMessage.value = 'شرکت جاری مشخص نشده است.'
    return
  }
  saving.value = true
  errorMessage.value = ''
  try {
    await createParty({ ...form.value })
    dialog.value = false
    form.value = { type: 'organization', name: '', role: 'customer', phone: '', email: '' }
    await load()
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'ثبت ناموفق'
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
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">طرف‌های تجاری</h4>
        <p class="text-body-1 text-medium-emphasis mb-0">مشتری، تأمین‌کننده، پیمانکار و سایر نقش‌ها</p>
      </div>
      <VBtn color="primary" prepend-icon="ri-user-add-line" @click="dialog = true">
        طرف تجاری جدید
      </VBtn>
    </div>

    <VAlert v-if="errorMessage" type="error" variant="tonal" class="mb-4">{{ errorMessage }}</VAlert>

    <VCard>
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
          <VChip size="small" color="primary" variant="tonal">
            {{ roleLabel[item.role] || item.role }}
          </VChip>
        </template>
        <template #no-data>
          داده‌ای نیست
        </template>
        <template #bottom />
      </VDataTable>
    </VCard>

    <VDialog v-model="dialog" max-width="560">
      <VCard title="ثبت طرف تجاری">
        <VCardText>
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
              <VTextField v-model="form.name" label="نام" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.phone" label="تلفن" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.email" label="ایمیل" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="dialog = false">انصراف</VBtn>
          <VBtn color="primary" :loading="saving" @click="save">ثبت</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
