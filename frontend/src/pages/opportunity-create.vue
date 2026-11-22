<script setup lang="ts">
import { useRouter } from 'vue-router'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import PhoneField from '@/components/forms/PhoneField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  createOpportunity,
  fetchOpportunityStages,
  getCompany,
  getUser,
} from '@/utils/api'
import { todayIso } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const router = useRouter()
const { success, error: notifyError } = useNotify()

const formRef = ref()
const saving = ref(false)
const stages = ref<{ title: string; value: number }[]>([])

/** پنل‌های اختیاری — پیش‌فرض بسته */
const openOptional = ref<string[]>([])

const user = computed(() => getUser<{ name?: string }>())
const assigneeLabel = computed(() => `خودم (${user.value?.name || 'کاربر جاری'})`)

const productItems = ['نوتوپیا', 'سایر محصولات چاپی', 'خدمات سفارشی']

const industryItems = [
  { title: '— انتخاب —', value: '' },
  { title: 'نفت و گاز', value: 'نفت و گاز' },
  { title: 'معدن', value: 'معدن' },
  { title: 'خودرو و قطعه‌سازی', value: 'خودرو و قطعه‌سازی' },
  { title: 'فناوری', value: 'فناوری' },
  { title: 'صنعتی عمومی', value: 'صنعتی عمومی' },
  { title: 'خدمات مالی', value: 'خدمات مالی' },
  { title: 'سایر', value: 'سایر' },
]

const roleItems = [
  { title: '— انتخاب —', value: '' },
  { title: 'تصمیم‌گیرنده', value: 'تصمیم‌گیرنده' },
  { title: 'تأثیرگذار', value: 'تأثیرگذار' },
  { title: 'کاربر', value: 'کاربر' },
  { title: 'معرف', value: 'معرف' },
]

const budgetItems = [
  { title: '— انتخاب —', value: '' },
  { title: 'پایین', value: 'پایین' },
  { title: 'متوسط', value: 'متوسط' },
  { title: 'بالا', value: 'بالا' },
  { title: 'نامشخص', value: 'نامشخص' },
]

const valueItems = [
  { title: '— انتخاب —', value: '' },
  { title: 'تجربه متفاوت و تصویر برند', value: 'تجربه متفاوت و تصویر برند' },
  { title: 'فناوری و قابلیت هوشمند', value: 'فناوری و قابلیت هوشمند' },
  { title: 'کاربرد واقعی و حفظ اطلاعات', value: 'کاربرد واقعی و حفظ اطلاعات' },
  { title: 'تمایز و ماندگاری هدیه', value: 'تمایز و ماندگاری هدیه' },
  { title: 'سایر', value: 'سایر' },
]

const statusItems = [
  { title: 'فعال', value: 'active' },
  { title: 'در حال پرورش', value: 'nurturing' },
  { title: 'متوقف موقت', value: 'paused' },
  { title: 'بسته‌شده-موفق', value: 'won' },
  { title: 'بسته‌شده-ناموفق', value: 'lost' },
]

const lostReasonItems = [
  { title: '— فقط در صورت توقف/باخت —', value: '' },
  { title: 'قیمت', value: 'قیمت' },
  { title: 'زمان', value: 'زمان' },
  { title: 'بودجه', value: 'بودجه' },
  { title: 'تصمیم‌گیرنده', value: 'تصمیم‌گیرنده' },
  { title: 'رقابت', value: 'رقابت' },
  { title: 'محصول', value: 'محصول' },
  { title: 'مخاطب نامناسب', value: 'مخاطب نامناسب' },
  { title: 'عدم نیاز', value: 'عدم نیاز' },
  { title: 'سایر', value: 'سایر' },
]

const form = ref({
  organization_name: '',
  products: ['نوتوپیا'] as string[],
  industry: '',
  industry_note: '',
  contact_name: '',
  contact_role: '',
  contact_mobile: '',
  contact_phone: '',
  contact_email: '',
  alt_email: '',
  website: '',
  city: '',
  province: '',
  postal_code: '',
  address: '',
  messenger: '',
  application_need: '',
  tirage: '',
  budget_band: '',
  purchase_timing: '',
  customer_value_focus: '',
  value_note: '',
  main_objection: '',
  stage_id: null as number | null,
  status: 'active',
  lost_reason: '',
  lost_reason_note: '',
  result_note: '',
  next_step: '',
  next_step_due_on: todayIso() as string | null,
  lead_source_note: '',
  opening_note: '',
})

const showIndustryNote = computed(() => form.value.industry === 'سایر')
const showValueNote = computed(() => form.value.customer_value_focus === 'سایر')
const showResultNote = computed(() => ['won', 'lost'].includes(form.value.status))

function buildAddressBlob() {
  const lines = [
    form.value.address ? `آدرس: ${form.value.address}` : '',
    form.value.city ? `شهر: ${form.value.city}` : '',
    form.value.province ? `استان: ${form.value.province}` : '',
    form.value.postal_code ? `کدپستی: ${form.value.postal_code}` : '',
    form.value.website ? `وب‌سایت: ${form.value.website}` : '',
    form.value.alt_email ? `ایمیل دوم: ${form.value.alt_email}` : '',
    form.value.messenger ? `پیام‌رسان: ${form.value.messenger}` : '',
  ].filter(Boolean)
  return lines.join('\n') || null
}

async function loadStages() {
  try {
    const res = await fetchOpportunityStages()
    stages.value = (res.data || []).map((s: any) => ({ title: s.name, value: s.id }))
    if (!form.value.stage_id && stages.value.length)
      form.value.stage_id = stages.value[0].value
  }
  catch (e: any) {
    notifyError(e?.message || 'خطا در دریافت مراحل')
  }
}

async function submit() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid)
    return

  if (!getCompany()?.id) {
    notifyError('شرکت جاری مشخص نشده است.')
    return
  }
  if (!form.value.products?.length) {
    notifyError('حداقل یک محصول را انتخاب کنید.')
    return
  }
  if (!form.value.stage_id) {
    notifyError('مرحله قیف را انتخاب کنید.')
    return
  }

  let valueFocus = form.value.customer_value_focus || null
  if (valueFocus === 'سایر' && form.value.value_note)
    valueFocus = `سایر: ${form.value.value_note}`

  let lostReason = form.value.lost_reason || null
  if (lostReason === 'سایر' && form.value.lost_reason_note)
    lostReason = `سایر: ${form.value.lost_reason_note}`

  const openingParts = [
    form.value.lead_source_note ? `منبع/شروع: ${form.value.lead_source_note}` : '',
    form.value.opening_note || '',
  ].filter(Boolean)

  saving.value = true
  try {
    const res = await createOpportunity({
      organization_name: form.value.organization_name,
      products: form.value.products,
      industry: form.value.industry || null,
      industry_note: form.value.industry_note || null,
      contact_name: form.value.contact_name || null,
      contact_role: form.value.contact_role || null,
      contact_mobile: form.value.contact_mobile || null,
      contact_phone: form.value.contact_phone || null,
      contact_email: form.value.contact_email || null,
      address: buildAddressBlob(),
      application_need: form.value.application_need || null,
      tirage: form.value.tirage || null,
      budget_band: form.value.budget_band || null,
      purchase_timing: form.value.purchase_timing || null,
      customer_value_focus: valueFocus,
      main_objection: form.value.main_objection || null,
      stage_id: form.value.stage_id,
      status: form.value.status,
      lost_reason: lostReason,
      result_note: form.value.result_note || null,
      next_step: form.value.next_step,
      next_step_due_on: form.value.next_step_due_on,
      opening_note: openingParts.join('\n') || null,
    })
    success('فرصت با موفقیت ثبت شد')
    if (res.data?.id)
      router.push('/opportunities/' + res.data.id)
    else
      router.push('/opportunities')
  }
  catch (e: any) {
    notifyError(e?.message || 'ثبت ناموفق بود')
  }
  finally {
    saving.value = false
  }
}

onMounted(loadStages)
</script>

<template>
  <div class="opp-create">
    <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-4">
      <div>
        <VBtn
          variant="text"
          color="secondary"
          size="small"
          class="mb-0 px-0"
          prepend-icon="ri-arrow-right-line"
          @click="$router.push('/opportunities')"
        >
          فرصت‌ها
        </VBtn>
        <h5 class="text-h5 mb-0">
          ثبت کامل فرصت
        </h5>
      </div>
      <div class="d-flex gap-2">
        <VBtn
          variant="tonal"
          color="secondary"
          size="small"
          @click="$router.push('/opportunities')"
        >
          انصراف
        </VBtn>
        <VBtn
          color="primary"
          size="small"
          prepend-icon="ri-save-3-line"
          :loading="saving"
          @click="submit"
        >
          ثبت فرصت
        </VBtn>
      </div>
    </div>

    <VForm
      ref="formRef"
      class="opp-create__form"
      @submit.prevent="submit"
    >
      <!-- ۱ ضروری -->
      <VCard class="mb-3 section-card section-card--required">
        <VCardItem class="py-2 px-4">
          <template #prepend>
            <VAvatar
              color="success"
              variant="tonal"
              size="32"
              rounded
            >
              <VIcon
                icon="ri-building-2-line"
                size="18"
              />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-medium">
            ۱. سازمان و تماس
          </VCardTitle>
          <template #append>
            <VChip
              color="success"
              size="x-small"
              variant="tonal"
            >
              ضروری
            </VChip>
          </template>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-4">
          <VRow dense>
            <VCol cols="12" md="6">
              <VTextField
                v-model="form.organization_name"
                label="نام سازمان / شرکت"
                placeholder="مثال: شرکت صنایع غذایی آفتاب"
                :rules="[rules.required('نام سازمان')]"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.products"
                :items="productItems"
                label="محصول"
                multiple
                chips
                closable-chips
                :rules="[rules.required('محصول')]"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.industry"
                :items="industryItems"
                label="صنعت"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="form.industry_note"
                label="توضیح صنعت (سایر)"
                :disabled="!showIndustryNote"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="form.contact_name"
                label="نام فرد تماس"
                placeholder="کسی که با او در ارتباطید"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.contact_role"
                :items="roleItems"
                label="نقش در تصمیم خرید"
              />
            </VCol>
            <VCol cols="12" md="6">
              <PhoneField
                v-model="form.contact_mobile"
                label="موبایل"
                mobile
              />
            </VCol>
            <VCol cols="12" md="6">
              <PhoneField
                v-model="form.contact_phone"
                label="تلفن ثابت"
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- اختیاری‌ها: expansion -->
      <VExpansionPanels
        v-model="openOptional"
        multiple
        class="mb-3 optional-panels"
      >
        <!-- ۲ -->
        <VExpansionPanel value="contact">
          <VExpansionPanelTitle class="text-body-2">
            <div class="d-flex align-center gap-2 w-100 pe-2">
              <VAvatar
                color="info"
                variant="tonal"
                size="28"
                rounded
              >
                <VIcon
                  icon="ri-mail-line"
                  size="16"
                />
              </VAvatar>
              <span class="font-weight-medium">۲. تماس تکمیلی و آدرس</span>
              <VSpacer />
              <VChip
                size="x-small"
                variant="tonal"
                color="secondary"
              >
                اختیاری
              </VChip>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <VRow dense>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.contact_email"
                  label="ایمیل اصلی"
                  type="email"
                  placeholder="name@company.com"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.alt_email"
                  label="ایمیل دوم"
                  type="email"
                  placeholder="برای مکاتبات موازی"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.website"
                  label="وب‌سایت"
                  placeholder="https://..."
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.messenger"
                  label="پیام‌رسان (تلگرام / واتساپ)"
                  placeholder="@username یا شماره"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.province"
                  label="استان"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.city"
                  label="شهر"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.postal_code"
                  label="کد پستی"
                  inputmode="numeric"
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.address"
                  label="آدرس / محل فعالیت"
                  placeholder="خیابان، پلاک، واحد..."
                />
              </VCol>
            </VRow>
          </VExpansionPanelText>
        </VExpansionPanel>

        <!-- ۳ -->
        <VExpansionPanel value="meta">
          <VExpansionPanelTitle class="text-body-2">
            <div class="d-flex align-center gap-2 w-100 pe-2">
              <VAvatar
                color="warning"
                variant="tonal"
                size="28"
                rounded
              >
                <VIcon
                  icon="ri-funds-line"
                  size="16"
                />
              </VAvatar>
              <span class="font-weight-medium">۳. نیاز، بودجه و ارزش</span>
              <VSpacer />
              <VChip
                size="x-small"
                variant="tonal"
                color="secondary"
              >
                اختیاری
              </VChip>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <VRow dense>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.application_need"
                  label="کاربرد خرید / نیاز"
                  placeholder="هدیه سازمانی، برندینگ..."
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.tirage"
                  label="تیراژ تقریبی"
                  placeholder="مثلاً ۲۵۰۰ جلد"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VSelect
                  v-model="form.budget_band"
                  :items="budgetItems"
                  label="بودجه تقریبی"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.purchase_timing"
                  label="زمان خرید احتمالی"
                  placeholder="فوری / تا پایان ماه"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VSelect
                  v-model="form.customer_value_focus"
                  :items="valueItems"
                  label="ارزش مورد توجه مشتری"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.value_note"
                  label="توضیح ارزش (سایر)"
                  :disabled="!showValueNote"
                />
              </VCol>
              <VCol cols="12">
                <VTextarea
                  v-model="form.main_objection"
                  label="نگرانی یا اعتراض اصلی"
                  rows="2"
                  auto-grow
                />
              </VCol>
            </VRow>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <!-- ۴ قیف -->
      <VCard class="mb-3 section-card section-card--funnel">
        <VCardItem class="py-2 px-4">
          <template #prepend>
            <VAvatar
              color="primary"
              variant="tonal"
              size="32"
              rounded
            >
              <VIcon
                icon="ri-filter-3-line"
                size="18"
              />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-medium">
            ۴. مرحله قیف و وضعیت
          </VCardTitle>
          <template #append>
            <VChip
              color="primary"
              size="x-small"
              variant="tonal"
            >
              قیف ضروری
            </VChip>
          </template>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-4">
          <VRow dense>
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.stage_id"
                :items="stages"
                label="مرحله قیف"
                :rules="[rules.required('مرحله قیف')]"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.status"
                :items="statusItems"
                label="وضعیت فرصت"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.lost_reason"
                :items="lostReasonItems"
                label="دلیل توقف یا باخت"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="form.lost_reason_note"
                label="توضیح دلیل (سایر)"
                :disabled="form.lost_reason !== 'سایر'"
              />
            </VCol>
            <VCol
              v-if="showResultNote"
              cols="12"
            >
              <VTextarea
                v-model="form.result_note"
                label="نتیجه نهایی"
                rows="2"
                auto-grow
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- ۵ گام -->
      <VCard class="mb-3 section-card section-card--step">
        <VCardItem class="py-2 px-4">
          <template #prepend>
            <VAvatar
              color="success"
              variant="flat"
              size="32"
              rounded
            >
              <VIcon
                icon="ri-flag-line"
                size="18"
                color="white"
              />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-medium">
            ۵. گام اول پیگیری
          </VCardTitle>
          <template #append>
            <VChip
              color="success"
              size="x-small"
              variant="flat"
            >
              ضروری ثبت
            </VChip>
          </template>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-4">
          <VRow dense>
            <VCol cols="12">
              <VTextarea
                v-model="form.next_step"
                label="گام بعدی"
                placeholder="مثال: تماس سه‌شنبه برای هماهنگی جلسه"
                rows="2"
                auto-grow
                :rules="[rules.required('گام بعدی')]"
              />
            </VCol>
            <VCol cols="12" md="6">
              <JalaliDateField
                v-model="form.next_step_due_on"
                label="مهلت گام"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                :model-value="assigneeLabel"
                label="مسئول پیگیری"
                readonly
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- ۶ یادداشت -->
      <VExpansionPanels
        v-model="openOptional"
        multiple
        class="mb-4 optional-panels"
      >
        <VExpansionPanel value="notes">
          <VExpansionPanelTitle class="text-body-2">
            <div class="d-flex align-center gap-2 w-100 pe-2">
              <VAvatar
                color="secondary"
                variant="tonal"
                size="28"
                rounded
              >
                <VIcon
                  icon="ri-sticky-note-line"
                  size="16"
                />
              </VAvatar>
              <span class="font-weight-medium">۶. یادداشت شروع</span>
              <VSpacer />
              <VChip
                size="x-small"
                variant="tonal"
                color="secondary"
              >
                اختیاری
              </VChip>
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <VRow dense>
              <VCol cols="12">
                <VTextarea
                  v-model="form.lead_source_note"
                  label="منبع سرنخ / یادداشت شروع"
                  placeholder="نمایشگاه / تماس سرد / معرفی"
                  rows="2"
                  auto-grow
                />
              </VCol>
              <VCol cols="12">
                <VTextarea
                  v-model="form.opening_note"
                  label="یادداشت آزاد"
                  rows="2"
                  auto-grow
                />
              </VCol>
            </VRow>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <div class="d-flex flex-wrap justify-end gap-2 pb-6">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="$router.push('/opportunities')"
        >
          انصراف
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-save-3-line"
          :loading="saving"
          @click="submit"
        >
          ثبت فرصت
        </VBtn>
      </div>
    </VForm>
  </div>
</template>

<style scoped>
.section-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.section-card--required {
  border-inline-start: 3px solid rgb(var(--v-theme-success));
}
.section-card--funnel {
  border-inline-start: 3px solid rgb(var(--v-theme-primary));
}
.section-card--step {
  border-inline-start: 3px solid rgb(var(--v-theme-success));
  background: rgba(var(--v-theme-success), 0.03);
}

.optional-panels :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  margin-bottom: 0.5rem !important;
  border-radius: 8px !important;
  overflow: hidden;
}
.optional-panels :deep(.v-expansion-panel-title) {
  min-height: 48px;
  padding-block: 8px;
}
.optional-panels :deep(.v-expansion-panel-text__wrapper) {
  padding: 12px 16px 16px;
}
</style>
