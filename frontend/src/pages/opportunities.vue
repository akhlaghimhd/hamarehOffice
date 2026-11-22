<script setup lang="ts">
import { createOpportunity, fetchOpportunities, fetchParties, getCompany } from '@/utils/api'

const items = ref<any[]>([])
const parties = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const dialog = ref(false)

const form = ref({
  title: '',
  party_id: null as number | null,
  amount: null as number | null,
  next_step: '',
  need_summary: '',
})

async function load() {
  if (!getCompany()?.id) {
    errorMessage.value = 'شرکت جاری مشخص نشده است.'
    return
  }
  loading.value = true
  errorMessage.value = ''
  try {
    const [oppRes, partyRes] = await Promise.all([fetchOpportunities(), fetchParties()])
    items.value = oppRes.data || []
    parties.value = (partyRes.data || []).map((r: any) => ({
      title: r.party?.name,
      value: r.party?.id,
    })).filter((p: any) => p.value)
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'خطا در دریافت'
  }
  finally {
    loading.value = false
  }
}

async function save() {
  if (!form.value.party_id) {
    errorMessage.value = 'طرف تجاری را انتخاب کنید.'
    return
  }
  saving.value = true
  errorMessage.value = ''
  try {
    await createOpportunity({
      title: form.value.title,
      party_id: form.value.party_id,
      amount: form.value.amount,
      next_step: form.value.next_step,
      need_summary: form.value.need_summary,
    })
    dialog.value = false
    form.value = { title: '', party_id: null, amount: null, next_step: '', need_summary: '' }
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
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 mb-1">فرصت‌های فروش</h4>
        <p class="text-body-1 text-medium-emphasis mb-0">از سرنخ تا برنده شدن</p>
      </div>
      <VBtn color="primary" prepend-icon="ri-add-line" @click="dialog = true">
        فرصت جدید
      </VBtn>
    </div>

    <VAlert v-if="errorMessage" type="error" variant="tonal" class="mb-4">{{ errorMessage }}</VAlert>

    <VCard>
      <VDataTable
        :headers="[
          { title: 'عنوان', key: 'title' },
          { title: 'طرف تجاری', key: 'party.name' },
          { title: 'مرحله', key: 'stage.name' },
          { title: 'مبلغ', key: 'amount' },
          { title: 'گام بعد', key: 'next_step' },
          { title: 'وضعیت', key: 'status' },
        ]"
        :items="items"
        :loading="loading"
        class="text-no-wrap"
      >
        <template #item.status="{ item }">
          <VChip size="small" variant="tonal">{{ item.status }}</VChip>
        </template>
        <template #no-data>فرصتی ثبت نشده</template>
        <template #bottom />
      </VDataTable>
    </VCard>

    <VDialog v-model="dialog" max-width="560">
      <VCard title="فرصت جدید">
        <VCardText>
          <VRow>
            <VCol cols="12">
              <VTextField v-model="form.title" label="عنوان فرصت" />
            </VCol>
            <VCol cols="12">
              <VSelect v-model="form.party_id" :items="parties" label="طرف تجاری" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model.number="form.amount" type="number" label="مبلغ تقریبی" />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField v-model="form.next_step" label="گام بعدی" />
            </VCol>
            <VCol cols="12">
              <VTextarea v-model="form.need_summary" label="شرح نیاز" rows="3" />
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
