<script setup lang="ts">
import {
  createOpportunity,
  createOpportunityActivity,
  fetchOpportunities,
  fetchOpportunityStages,
  fetchParties,
  getCompany,
  updateOpportunityStage,
} from '@/utils/api'

const items = ref<any[]>([])
const parties = ref<any[]>([])
const stages = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const dialog = ref(false)
const detailDialog = ref(false)
const selected = ref<any | null>(null)
const noteBody = ref('')
const noteSaving = ref(false)

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
    const [oppRes, partyRes, stageRes] = await Promise.all([
      fetchOpportunities(),
      fetchParties(),
      fetchOpportunityStages(),
    ])
    items.value = oppRes.data || []
    parties.value = (partyRes.data || [])
      .map((r: any) => ({ title: r.party?.name, value: r.party?.id }))
      .filter((p: any) => p.value)
    stages.value = (stageRes.data || []).map((s: any) => ({
      title: s.name,
      value: s.id,
    }))
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

function openDetail(item: any) {
  selected.value = { ...item }
  noteBody.value = ''
  detailDialog.value = true
}

async function changeStage(stageId: number) {
  if (!selected.value) return
  try {
    const res = await updateOpportunityStage(selected.value.id, stageId)
    selected.value = { ...selected.value, ...res.data }
    await load()
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'تغییر مرحله ناموفق'
  }
}

async function addNote() {
  if (!selected.value || !noteBody.value.trim()) return
  noteSaving.value = true
  try {
    await createOpportunityActivity(selected.value.id, {
      type: 'note',
      body: noteBody.value,
    })
    noteBody.value = ''
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'ثبت یادداشت ناموفق'
  }
  finally {
    noteSaving.value = false
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
          { title: '', key: 'actions', sortable: false },
        ]"
        :items="items"
        :loading="loading"
        class="text-no-wrap"
      >
        <template #item.status="{ item }">
          <VChip size="small" variant="tonal">{{ item.status }}</VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn size="small" variant="text" @click="openDetail(item)">جزئیات</VBtn>
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

    <VDialog v-model="detailDialog" max-width="640">
      <VCard v-if="selected" :title="selected.title">
        <VCardText>
          <div class="mb-4">
            <div class="text-body-2 text-medium-emphasis">طرف تجاری</div>
            <div class="text-h6">{{ selected.party?.name || '—' }}</div>
          </div>

          <VSelect
            :model-value="selected.stage?.id"
            :items="stages"
            label="مرحله"
            class="mb-4"
            @update:model-value="changeStage"
          />

          <VTextField
            v-model="noteBody"
            label="یادداشت فعالیت"
            class="mb-2"
          />
          <VBtn size="small" :loading="noteSaving" @click="addNote">ثبت یادداشت</VBtn>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="text" @click="detailDialog = false">بستن</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
