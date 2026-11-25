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
import { formatJalaliFa, toFaDigits, todayIso } from '@/utils/fa'
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
const suppressDirty = ref(false)

const filters = ref({
  status: '',
  stage_id: '',
  owner_user_id: '',
  followup_user_id: '',
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
  const followup = item.followup_user?.name || item.followup_name || item.assignee?.name
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

function stopAnd(fn: () => void) {
  return (e: Event) => {
    e.stopPropagation()
    fn()
  }
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
    if (filters.value.owner_user_id)
      params.set('owner_user_id', String(filters.value.owner_user_id))
    if (filters.value.followup_user_id)
      params.set('followup_user_id', String(filters.value.followup_user_id))
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
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-4 mb-md-6">
      <div>
        <h4 class="text-h4 mb-1">
          فرصت‌ها
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          همه فرصت‌ها · {{ toFaDigits(items.length) }} مورد
        </p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <VBtn
          variant="tonal"
          color="primary"
          prepend-icon="ri-file-list-3-line"
          :to="{ path: '/opportunities/create' }"
        >
          ثبت کامل
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openCreate"
        >
          ثبت سریع
        </VBtn>
      </div>
    </div>

    <VCard class="mb-4">
      <VCardText class="d-flex flex-wrap gap-3 align-center">
        <VTextField
          v-model="filters.q"
          density="compact"
          hide-details
          label="جستجوی سازمان، کد، مخاطب..."
          prepend-inner-icon="ri-search-line"
          style="min-inline-size: 14rem; flex: 1;"
          @keyup.enter="load"
        />
        <VSelect
          v-model="filters.status"
          :items="statusItems"
          density="compact"
          hide-details
          style="min-inline-size: 10rem;"
          @update:model-value="load"
        />
        <VSelect
          v-model="filters.stage_id"
          :items="stages"
          density="compact"
          hide-details
          style="min-inline-size: 10rem;"
          @update:model-value="load"
        />
        <VBtn
          variant="tonal"
          color="secondary"
          @click="load"
        >
          فیلتر
        </VBtn>
      </VCardText>
    </VCard>

    <VCard class="d-none d-md-block">
      <VDataTable
        :headers="tableHeaders"
        :items="items"
        :loading="loading"
        class="text-no-wrap opportunity-table"
        hover
        @click:row="goToOpportunity"
      >
        <template #item.code="{ item }">
          <span class="text-primary font-weight-medium">{{ toFaDigits(item.code) }}</span>
        </template>

        <template #item.organization_name="{ item }">
          <div class="py-1">
            <div class="font-weight-medium text-wrap">
              {{ item.organization_name }}
            </div>
            <div
              v-if="orgSubtitle(item)"
              class="text-caption text-medium-emphasis text-wrap"
            >
              {{ orgSubtitle(item) }}
            </div>
          </div>
        </template>

        <template #item.products="{ item }">
          <div class="d-flex flex-wrap gap-1 py-1">
            <VChip
              v-for="p in productsOf(item)"
              :key="p"
              size="small"
              variant="tonal"
              color="primary"
            >
              {{ p }}
            </VChip>
            <span v-if="!productsOf(item).length">—</span>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="statusColor[item.status] || 'secondary'"
          >
            {{ statusLabel[item.status] || item.status }}
          </VChip>
        </template>

        <template #item['stage.name']="{ item }">
          {{ item.stage?.name || '—' }}
        </template>

        <template #item.next_step="{ item }">
          <span class="text-wrap d-inline-block" style="max-inline-size: 12rem;">
            {{ item.next_step || '—' }}
          </span>
        </template>

        <template #item.next_step_due_on="{ item }">
          {{ formatJalaliFa(item.next_step_due_on) || '—' }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-end ga-0" @click.stop>
            <VBtn
              icon
              variant="text"
              size="small"
              color="default"
              title="ویرایش"
              :to="{ path: '/opportunities/' + item.id }"
            >
              <VIcon icon="ri-pencil-line" size="18" />
            </VBtn>
            <VBtn
              icon
              variant="text"
              size="small"
              color="success"
              title="بستن گام"
              @click="openItem(item.id)"
            >
              <VIcon icon="ri-checkbox-circle-line" size="18" />
            </VBtn>
            <VBtn
              icon
              variant="text"
              size="small"
              color="default"
              title="فعالیت"
              @click="openItem(item.id)"
            >
              <VIcon icon="ri-file-list-3-line" size="18" />
            </VBtn>
            <VBtn
              icon
              variant="text"
              size="small"
              color="default"
              title="ارجاع"
              @click="openItem(item.id)"
            >
              <VIcon icon="ri-user-add-line" size="18" />
            </VBtn>
            <VBtn
              icon
              variant="text"
              size="small"
              color="default"
              title="میزکار"
              @click="openItem(item.id)"
            >
              <VIcon icon="ri-arrow-go-back-line" size="18" />
            </VBtn>
          </div>
        </template>

        <template #no-data>
          فرصتی ثبت نشده
        </template>
        <template #bottom />
      </VDataTable>
    </VCard>

    <div class="d-md-none">
      <div v-if="loading" class="text-center py-8 text-medium-emphasis">
        در حال بارگذاری...
      </div>
      <div v-else-if="!items.length" class="text-center py-8 text-medium-emphasis">
        فرصتی ثبت نشده
      </div>
      <VCard
        v-for="item in items"
        :key="item.id"
        class="mb-3 mobile-list-card"
        @click="openItem(item.id)"
      >
        <VCardText class="pa-4">
          <div class="d-flex align-start justify-space-between gap-2 mb-2">
            <div>
              <div class="text-subtitle-1 font-weight-medium">
                {{ item.organization_name }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ toFaDigits(item.code) }}
                <template v-if="orgSubtitle(item)">
                  · {{ orgSubtitle(item) }}
                </template>
              </div>
            </div>
            <VChip
              size="small"
              variant="tonal"
              :color="statusColor[item.status] || 'primary'"
            >
              {{ statusLabel[item.status] || item.status }}
            </VChip>
          </div>
          <div class="d-flex flex-wrap gap-1 mb-2">
            <VChip
              v-for="p in productsOf(item)"
              :key="p"
              size="x-small"
              variant="outlined"
            >
              {{ p }}
            </VChip>
          </div>
          <div class="text-body-2 mb-1">
            <span class="text-medium-emphasis">مرحله: </span>{{ item.stage?.name || '—' }}
          </div>
          <div class="text-body-2 mb-1">
            <span class="text-medium-emphasis">گام بعدی: </span>{{ item.next_step || '—' }}
          </div>
          <div class="text-caption text-medium-emphasis mb-2">
            {{ formatJalaliFa(item.next_step_due_on) }}
          </div>
          <div class="d-flex justify-end ga-0" @click.stop>
            <VBtn icon variant="text" size="small" title="ویرایش" @click="openItem(item.id)">
              <VIcon icon="ri-pencil-line" size="18" />
            </VBtn>
            <VBtn icon variant="text" size="small" color="success" title="بستن گام" @click="openItem(item.id)">
              <VIcon icon="ri-checkbox-circle-line" size="18" />
            </VBtn>
            <VBtn icon variant="text" size="small" title="فعالیت" @click="openItem(item.id)">
              <VIcon icon="ri-file-list-3-line" size="18" />
            </VBtn>
            <VBtn icon variant="text" size="small" title="ارجاع" @click="openItem(item.id)">
              <VIcon icon="ri-user-add-line" size="18" />
            </VBtn>
          </div>
        </VCardText>
      </VCard>
    </div>

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
          @click="dirty = false; drawerOpen = false"
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
