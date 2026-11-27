<script setup lang="ts">
import { useRouter } from 'vue-router'
import FormDrawer from '@/components/forms/FormDrawer.vue'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import PhoneField from '@/components/forms/PhoneField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  assignOpportunity,
  closeOpportunityStep,
  createOpportunity,
  fetchCompanyUsers,
  fetchOpportunities,
  fetchOpportunityStages,
  getCompany,
  updateOpportunity,
} from '@/utils/api'
import { formatJalaliFa, toFaDigits, todayIso } from '@/utils/fa'
import { rules } from '@/utils/formRules'
import trophy from '@images/misc/trophy.png'

const router = useRouter()
const { success, error: notifyError } = useNotify()

const items = ref<any[]>([])
const stages = ref<any[]>([])
const companyUsers = ref<{ title: string; value: number }[]>([])
const loading = ref(false)
const saving = ref(false)
const actionSaving = ref(false)

const drawerOpen = ref(false)
const formRef = ref()
const dirty = ref(false)
const suppressDirty = ref(false)

const activeItem = ref<any | null>(null)

const closeOpen = ref(false)
const assignOpen = ref(false)
const editOpen = ref(false)

const closeFormRef = ref()
const assignFormRef = ref()
const editFormRef = ref()

const closeForm = ref({
  result: '',
  stage_id: null as number | null,
  status: 'active',
  next_step_title: '',
  next_step_due_on: null as string | null,
})

const assignForm = ref({
  to_user_id: null as number | null,
  note: '',
})

const editForm = ref({
  organization_name: '',
  products: [] as string[],
  contact_name: '',
  contact_mobile: '',
  contact_phone: '',
  next_step: '',
  next_step_due_on: null as string | null,
})

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
  next_step_due_on: todayIso() as string | null,
})

const form = ref(emptyForm())

watch(form, () => {
  if (!suppressDirty.value)
    dirty.value = true
}, { deep: true })

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

const statusColor: Record<string, string> = {
  active: 'success',
  nurturing: 'info',
  paused: 'warning',
  won: 'success',
  lost: 'error',
}

const productItems = ['نوتوپیا', 'سایر محصولات چاپی', 'خدمات سفارشی']

const stageSelectItems = computed(() =>
  stages.value.filter(s => s.value !== ''),
)

const tableHeaders = [
  { title: 'کد', key: 'code', sortable: true },
  { title: 'سازمان', key: 'organization_name', sortable: true },
  { title: 'محصول‌ها', key: 'products', sortable: false },
  { title: 'وضعیت', key: 'status', sortable: true },
  { title: 'مرحله', key: 'stage.name', sortable: false },
  { title: 'گام بعدی', key: 'next_step', sortable: false },
  { title: 'تاریخ گام', key: 'next_step_due_on', sortable: true },
  { title: '', key: 'actions', sortable: false, align: 'end' as const },
]

function productsOf(item: any): string[] {
  if (item.products?.length)
    return item.products
  if (item.product_name)
    return [item.product_name]
  return []
}

function orgSubtitle(item: any): string {
  const parts: string[] = []
  const owner = item.owner?.name || item.owner_name
  const followup = item.assignee?.name
  if (owner)
    parts.push(`مالک: ${owner}`)
  if (followup && followup !== owner)
    parts.push(`ارجاع به: ${followup}`)
  if (item.contact_name)
    parts.push(item.contact_name)
  return parts.join(' · ')
}

function openItem(id: number) {
  router.push('/opportunities/' + id)
}

function goToOpportunity(_event: unknown, ctx: { item: { id: number } }) {
  openItem(ctx.item.id)
}

function openEdit(item: any) {
  activeItem.value = item
  editForm.value = {
    organization_name: item.organization_name || '',
    products: productsOf(item),
    contact_name: item.contact_name || '',
    contact_mobile: item.contact_mobile || '',
    contact_phone: item.contact_phone || '',
    next_step: item.next_step || item.current_step?.title || '',
    next_step_due_on: item.next_step_due_on || item.current_step?.due_on || null,
  }
  editOpen.value = true
}

function openClose(item: any) {
  activeItem.value = item
  closeForm.value = {
    result: '',
    stage_id: item.stage?.id ?? null,
    status: item.status || 'active',
    next_step_title: '',
    next_step_due_on: null,
  }
  closeOpen.value = true
}

function openActivity(item: any) {
  router.push('/opportunities/' + item.id + '/activities/new')
}

async function openAssign(item: any) {
  activeItem.value = item
  assignForm.value = {
    to_user_id: item.assignee?.id ?? null,
    note: '',
  }
  if (!companyUsers.value.length) {
    try {
      const res = await fetchCompanyUsers()
      companyUsers.value = (res.data || []).map((u: any) => ({
        title: u.name,
        value: u.id,
      }))
    }
    catch (e: any) {
      notifyError(e?.message || 'خطا در دریافت کاربران')
      return
    }
  }
  assignOpen.value = true
}

async function openCreate() {
  suppressDirty.value = true
  form.value = emptyForm()
  dirty.value = false
  drawerOpen.value = true
  await nextTick()
  suppressDirty.value = false
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

async function submitEdit() {
  const { valid } = await editFormRef.value?.validate?.() ?? { valid: true }
  if (!valid || !activeItem.value)
    return

  actionSaving.value = true
  try {
    await updateOpportunity(activeItem.value.id, {
      organization_name: editForm.value.organization_name,
      products: editForm.value.products,
      contact_name: editForm.value.contact_name || null,
      contact_mobile: editForm.value.contact_mobile || null,
      contact_phone: editForm.value.contact_phone || null,
      next_step: editForm.value.next_step || null,
      next_step_due_on: editForm.value.next_step_due_on,
    })
    editOpen.value = false
    success('فرصت به‌روزرسانی شد')
    await load()
  }
  catch (e: any) {
    notifyError(e?.message || 'ویرایش ناموفق بود')
  }
  finally {
    actionSaving.value = false
  }
}

async function submitClose() {
  const { valid } = await closeFormRef.value?.validate?.() ?? { valid: true }
  if (!valid || !activeItem.value)
    return

  actionSaving.value = true
  try {
    await closeOpportunityStep(activeItem.value.id, {
      result: closeForm.value.result,
      stage_id: closeForm.value.stage_id,
      status: closeForm.value.status,
      next_step_title: closeForm.value.next_step_title || null,
      next_step_due_on: closeForm.value.next_step_due_on,
    })
    closeOpen.value = false
    success('گام بسته شد')
    await load()
  }
  catch (e: any) {
    notifyError(e?.message || 'بستن گام ناموفق بود')
  }
  finally {
    actionSaving.value = false
  }
}

async function submitAssign() {
  const { valid } = await assignFormRef.value?.validate?.() ?? { valid: true }
  if (!valid || !activeItem.value)
    return

  actionSaving.value = true
  try {
    await assignOpportunity(activeItem.value.id, {
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
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-4 mb-md-6">
      <div>
        <h4 class="text-h4 mb-1">فرصت‌ها</h4>
        <p class="text-body-2 text-medium-emphasis mb-0">همه فرصت‌ها · {{ toFaDigits(items.length) }} مورد</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <VBtn variant="tonal" color="primary" prepend-icon="ri-file-list-3-line" :to="{ path: '/opportunities/create' }">ثبت کامل</VBtn>
        <VBtn color="primary" prepend-icon="ri-add-line" @click="openCreate">ثبت سریع</VBtn>
      </div>
    </div>

    <VCard class="mb-4">
      <VCardText class="d-flex flex-wrap gap-3 align-center">
        <VTextField v-model="filters.q" density="compact" hide-details label="جستجوی سازمان، کد، مخاطب..." prepend-inner-icon="ri-search-line" style="min-inline-size: 14rem; flex: 1;" @keyup.enter="load" />
        <VSelect v-model="filters.status" :items="statusItems" density="compact" hide-details style="min-inline-size: 10rem;" @update:model-value="load" />
        <VSelect v-model="filters.stage_id" :items="stages" density="compact" hide-details style="min-inline-size: 10rem;" @update:model-value="load" />
        <VBtn variant="tonal" color="secondary" @click="load">فیلتر</VBtn>
      </VCardText>
    </VCard>

    <VCard class="d-none d-md-block">
      <VDataTable :headers="tableHeaders" :items="items" :loading="loading" class="text-no-wrap opportunity-table" hover @click:row="goToOpportunity">
        <template #item.code="{ item }">
          <span class="text-primary font-weight-medium">{{ toFaDigits(item.code) }}</span>
        </template>
        <template #item.organization_name="{ item }">
          <div class="py-1">
            <div class="font-weight-medium text-wrap">{{ item.organization_name }}</div>
            <div v-if="orgSubtitle(item)" class="text-caption text-medium-emphasis text-wrap">{{ orgSubtitle(item) }}</div>
          </div>
        </template>
        <template #item.products="{ item }">
          <div class="d-flex flex-wrap gap-1 py-1">
            <VChip v-for="p in productsOf(item)" :key="p" size="small" variant="tonal" color="primary">{{ p }}</VChip>
            <span v-if="!productsOf(item).length">—</span>
          </div>
        </template>
        <template #item.status="{ item }">
          <VChip size="small" variant="tonal" :color="statusColor[item.status] || 'secondary'">{{ statusLabel[item.status] || item.status }}</VChip>
        </template>
        <template #item['stage.name']="{ item }">{{ item.stage?.name || '—' }}</template>
        <template #item.next_step="{ item }">
          <span class="text-wrap d-inline-block" style="max-inline-size: 12rem;">{{ item.next_step || '—' }}</span>
        </template>
        <template #item.next_step_due_on="{ item }">{{ formatJalaliFa(item.next_step_due_on) || '—' }}</template>
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-end ga-0" @click.stop>
            <VBtn icon variant="text" size="small" title="ویرایش" @click="openEdit(item)"><VIcon icon="ri-pencil-line" size="18" /></VBtn>
            <VBtn icon variant="text" size="small" color="success" title="بستن گام" @click="openClose(item)"><VIcon icon="ri-checkbox-circle-line" size="18" /></VBtn>
            <VBtn icon variant="text" size="small" title="ثبت فعالیت" @click="openActivity(item)"><VIcon icon="ri-file-list-3-line" size="18" /></VBtn>
            <VBtn icon variant="text" size="small" title="ارجاع" @click="openAssign(item)"><VIcon icon="ri-user-add-line" size="18" /></VBtn>
            <VBtn icon variant="text" size="small" title="میزکار" @click="openItem(item.id)"><VIcon icon="ri-arrow-go-back-line" size="18" /></VBtn>
          </div>
        </template>
        <template #no-data>فرصتی ثبت نشده</template>
        <template #bottom />
      </VDataTable>
    </VCard>

    <div class="d-md-none">
      <div v-if="loading" class="text-center py-8 text-medium-emphasis">در حال بارگذاری...</div>
      <div v-else-if="!items.length" class="text-center py-8 text-medium-emphasis">فرصتی ثبت نشده</div>
      <VCard v-for="item in items" :key="item.id" class="mb-3 mobile-list-card" @click="openItem(item.id)">
        <VCardText class="pa-4">
          <div class="d-flex align-start justify-space-between gap-2 mb-2">
            <div>
              <div class="text-subtitle-1 font-weight-medium">{{ item.organization_name }}</div>
              <div class="text-caption text-medium-emphasis">{{ toFaDigits(item.code) }}<template v-if="orgSubtitle(item)"> · {{ orgSubtitle(item) }}</template></div>
            </div>
            <VChip size="small" variant="tonal" :color="statusColor[item.status] || 'primary'">{{ statusLabel[item.status] || item.status }}</VChip>
          </div>
          <div class="d-flex flex-wrap gap-1 mb-2">
            <VChip v-for="p in productsOf(item)" :key="p" size="x-small" variant="outlined">{{ p }}</VChip>
          </div>
          <div class="text-body-2 mb-1"><span class="text-medium-emphasis">مرحله: </span>{{ item.stage?.name || '—' }}</div>
          <div class="text-body-2 mb-1"><span class="text-medium-emphasis">گام بعدی: </span>{{ item.next_step || '—' }}</div>
          <div class="text-caption text-medium-emphasis mb-2">{{ formatJalaliFa(item.next_step_due_on) }}</div>
          <div class="d-flex justify-end ga-0" @click.stop>
            <VBtn icon variant="text" size="small" title="ویرایش" @click="openEdit(item)"><VIcon icon="ri-pencil-line" size="18" /></VBtn>
            <VBtn icon variant="text" size="small" color="success" title="بستن گام" @click="openClose(item)"><VIcon icon="ri-checkbox-circle-line" size="18" /></VBtn>
            <VBtn icon variant="text" size="small" title="فعالیت" @click="openActivity(item)"><VIcon icon="ri-file-list-3-line" size="18" /></VBtn>
            <VBtn icon variant="text" size="small" title="ارجاع" @click="openAssign(item)"><VIcon icon="ri-user-add-line" size="18" /></VBtn>
          </div>
        </VCardText>
      </VCard>
    </div>

    <FormDrawer v-model="drawerOpen" title="ثبت سریع فرصت" subtitle="حداقل اطلاعات برای شروع پیگیری" :dirty="dirty" :illustration="trophy" :width="520">
      <VForm ref="formRef" @submit.prevent="saveQuick">
        <VRow>
          <VCol cols="12"><VTextField v-model="form.organization_name" label="سازمان" :rules="[rules.required('سازمان')]" /></VCol>
          <VCol cols="12"><VSelect v-model="form.products" :items="productItems" label="محصول‌ها" multiple chips closable-chips :rules="[rules.required('محصول')]" /></VCol>
          <VCol cols="12" sm="6"><VTextField v-model="form.contact_name" label="فرد تماس" /></VCol>
          <VCol cols="12" sm="6"><PhoneField v-model="form.contact_mobile" label="موبایل" mobile /></VCol>
          <VCol cols="12" sm="6"><PhoneField v-model="form.contact_phone" label="تلفن" /></VCol>
          <VCol cols="12" sm="6"><JalaliDateField v-model="form.next_step_due_on" label="تاریخ گام بعدی" /></VCol>
          <VCol cols="12"><VTextarea v-model="form.next_step" label="گام بعدی" rows="2" :rules="[rules.required('گام بعدی')]" /></VCol>
        </VRow>
      </VForm>
      <template #actions>
        <VBtn variant="text" color="secondary" @click="dirty = false; drawerOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="saving" @click="saveQuick">ثبت و مشاهده</VBtn>
      </template>
    </FormDrawer>

    <FormDrawer v-model="editOpen" title="ویرایش فرصت" :subtitle="activeItem ? toFaDigits(activeItem.code) : ''" :width="520">
      <VForm ref="editFormRef" @submit.prevent="submitEdit">
        <VTextField v-model="editForm.organization_name" label="سازمان" class="mb-3" :rules="[rules.required('سازمان')]" />
        <VSelect v-model="editForm.products" :items="productItems" label="محصول‌ها" multiple chips closable-chips class="mb-3" />
        <VTextField v-model="editForm.contact_name" label="فرد تماس" class="mb-3" />
        <VTextField v-model="editForm.contact_mobile" label="موبایل" class="mb-3" />
        <VTextarea v-model="editForm.next_step" label="گام بعدی" rows="2" class="mb-3" />
        <JalaliDateField v-model="editForm.next_step_due_on" label="تاریخ گام" />
      </VForm>
      <template #actions>
        <VBtn variant="text" color="secondary" @click="editOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="actionSaving" @click="submitEdit">ذخیره</VBtn>
      </template>
    </FormDrawer>

    <FormDrawer v-model="closeOpen" title="بستن گام" :subtitle="activeItem ? (activeItem.next_step || activeItem.current_step?.title || '') : ''" :width="480">
      <VForm ref="closeFormRef" @submit.prevent="submitClose">
        <VTextarea v-model="closeForm.result" label="نتیجه این گام" rows="3" class="mb-3" :rules="[rules.required('نتیجه')]" />
        <VSelect v-model="closeForm.stage_id" :items="stageSelectItems" label="مرحله قیف" class="mb-3" />
        <VSelect v-model="closeForm.status" :items="statusItems.filter(s => s.value)" label="وضعیت فرصت" class="mb-3" />
        <VTextField v-model="closeForm.next_step_title" label="گام بعدی (اختیاری)" class="mb-3" />
        <JalaliDateField v-model="closeForm.next_step_due_on" label="مهلت گام بعدی" />
      </VForm>
      <template #actions>
        <VBtn variant="text" color="secondary" @click="closeOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="actionSaving" @click="submitClose">بستن گام</VBtn>
      </template>
    </FormDrawer>

    <FormDrawer v-model="assignOpen" title="ارجاع فرصت" :subtitle="activeItem?.organization_name || ''" :width="440">
      <VForm ref="assignFormRef" @submit.prevent="submitAssign">
        <VSelect v-model="assignForm.to_user_id" :items="companyUsers" label="ارجاع به" class="mb-3" :rules="[rules.required('کاربر')]" />
        <VTextarea v-model="assignForm.note" label="یادداشت (اختیاری)" rows="3" />
      </VForm>
      <template #actions>
        <VBtn variant="text" color="secondary" @click="assignOpen = false">انصراف</VBtn>
        <VBtn color="primary" :loading="actionSaving" @click="submitAssign">ارجاع</VBtn>
      </template>
    </FormDrawer>
  </div>
</template>

<style scoped>
.mobile-list-card {
  cursor: pointer;
  transition: box-shadow 0.15s ease;
}
.mobile-list-card:active {
  box-shadow: 0 2px 8px rgba(var(--v-shadow-key-umbra-color), 0.12);
}
.opportunity-table :deep(tbody tr) {
  cursor: pointer;
}
</style>
