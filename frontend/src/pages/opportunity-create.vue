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
import trophy from '@images/misc/trophy.png'

const router = useRouter()
const { success, error: notifyError } = useNotify()

const formRef = ref()
const saving = ref(false)
const activeTab = ref('identity')
const stages = ref<{ title: string; value: number }[]>([])

const tabs = [
  { title: 'هویت و تماس', icon: 'ri-building-2-line', tab: 'identity' },
  { title: 'تماس تکمیلی', icon: 'ri-mail-line', tab: 'contact' },
  { title: 'نیاز و بودجه', icon: 'ri-funds-line', tab: 'meta' },
  { title: 'قیف فروش', icon: 'ri-filter-3-line', tab: 'funnel' },
  { title: 'گام اول', icon: 'ri-flag-line', tab: 'step' },
  { title: 'یادداشت', icon: 'ri-sticky-note-line', tab: 'notes' },
]

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

function goNext() {
  const idx = tabs.findIndex(t => t.tab === activeTab.value)
  if (idx < tabs.length - 1)
    activeTab.value = tabs[idx + 1].tab
}

async function submit() {
  const { valid } = await formRef.value?.validate?.() ?? { valid: true }
  if (!valid) {
    if (!form.value.organization_name || !form.value.products?.length)
      activeTab.value = 'identity'
    else if (!form.value.stage_id)
      activeTab.value = 'funnel'
    else if (!form.value.next_step)
      activeTab.value = 'step'
    notifyError('فیلدهای ضروری را تکمیل کنید.')
    return
  }

  if (!getCompany()?.id) {
    notifyError('شرکت جاری مشخص نشده است.')
    return
  }
  if (!form.value.products?.length) {
    activeTab.value = 'identity'
    notifyError('حداقل یک محصول را انتخاب کنید.')
    return
  }
  if (!form.value.stage_id) {
    activeTab.value = 'funnel'
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
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-5">
      <div>
        <VBtn
          variant="text"
          color="secondary"
          size="small"
          class="px-0 mb-1"
          prepend-icon="ri-arrow-right-line"
          @click="$router.push('/opportunities')"
        >
          فرصت‌ها
        </VBtn>
        <h4 class="text-h4 mb-0">
          ثبت کامل فرصت
        </h4>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <VBtn
          color="secondary"
          variant="outlined"
          @click="$router.push('/opportunities')"
        >
          انصراف
        </VBtn>
        <VBtn
          color="primary"
          :loading="saving"
          @click="submit"
        >
          ثبت فرصت
        </VBtn>
      </div>
    </div>

    <VTabs
      v-model="activeTab"
      show-arrows
      class="v-tabs-pill"
    >
      <VTab
        v-for="item in tabs"
        :key="item.tab"
        :value="item.tab"
      >
        <VIcon
          size="18"
          start
          :icon="item.icon"
        />
        {{ item.title }}
      </VTab>
    </VTabs>

    <VForm
      ref="formRef"
      @submit.prevent="submit"
    >
      <VWindow
        v-model="activeTab"
        class="mt-5 disable-tab-transition"
        :touch="false"
      >
        <VWindowItem value="identity">
          <VCard title="جزئیات سازمان و تماس">
            <VCardText class="d-flex align-center flex-wrap gap-4">
              <VAvatar
                rounded="lg"
                size="72"
                color="primary"
                variant="tonal"
              >
                <VImg
                  :src="trophy"
                  width="56"
                  height="56"
                />
              </VAvatar>
              <div>
                <div class="text-body-1 font-weight-medium">
                  اطلاعات پایه سرنخ
                </div>
                <div class="text-body-2 text-medium-emphasis">
                  فیلدهای ستاره‌دار برای ثبت ضروری هستند. بقیه را می‌توانید بعداً تکمیل کنید.
                </div>
              </div>
            </VCardText>

            <VDivider />

            <VCardText>
              <VRow>
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
                    placeholder="انتخاب صنعت"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="form.industry_note"
                    label="توضیح صنعت (سایر)"
                    :disabled="!showIndustryNote"
                    placeholder="در صورت انتخاب سایر"
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
                    placeholder="انتخاب نقش"
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
                <VCol cols="12" class="d-flex flex-wrap gap-4">
                  <VBtn @click="goNext">
                    ادامه
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="$router.push('/opportunities')"
                  >
                    انصراف
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VWindowItem>

        <VWindowItem value="contact">
          <VCard title="تماس تکمیلی و آدرس">
            <VCardText>
              <p class="text-body-2 text-medium-emphasis mb-6">
                اختیاری — برای پیگیری و ارسال پیشنهاد مفید است.
              </p>
              <VRow>
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
                    placeholder="مکاتبات موازی"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="form.website"
                    label="وب‌سایت"
                    placeholder="https://example.com"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="form.messenger"
                    label="پیام‌رسان"
                    placeholder="تلگرام / واتساپ"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="form.province"
                    label="استان"
                    placeholder="تهران"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="form.city"
                    label="شهر"
                    placeholder="تهران"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="form.postal_code"
                    label="کد پستی"
                    placeholder="۱۲۳۴۵۶۷۸۹۰"
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
                <VCol cols="12" class="d-flex flex-wrap gap-4">
                  <VBtn @click="goNext">
                    ادامه
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="activeTab = 'identity'"
                  >
                    قبلی
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VWindowItem>

        <VWindowItem value="meta">
          <VCard title="نیاز، بودجه و ارزش">
            <VCardText>
              <p class="text-body-2 text-medium-emphasis mb-6">
                اختیاری — کیفیت سرنخ را بهتر می‌کند.
              </p>
              <VRow>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="form.application_need"
                    label="کاربرد خرید / نیاز"
                    placeholder="هدیه سازمانی، برندینگ، نمایشگاه..."
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
                    placeholder="فوری / تا پایان ماه / فصل بعد"
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
                    placeholder="اگر مخالفت یا دغدغه‌ای مطرح شده"
                    rows="3"
                  />
                </VCol>
                <VCol cols="12" class="d-flex flex-wrap gap-4">
                  <VBtn @click="goNext">
                    ادامه
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="activeTab = 'contact'"
                  >
                    قبلی
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VWindowItem>

        <VWindowItem value="funnel">
          <VCard title="مرحله قیف و وضعیت">
            <VCardText>
              <VRow>
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
                    rows="3"
                  />
                </VCol>
                <VCol cols="12" class="d-flex flex-wrap gap-4">
                  <VBtn @click="goNext">
                    ادامه
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="activeTab = 'meta'"
                  >
                    قبلی
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VWindowItem>

        <VWindowItem value="step">
          <VCard title="گام اول پیگیری">
            <VCardText>
              <p class="text-body-2 text-medium-emphasis mb-6">
                مشخص کنید بعد از ثبت چه کاری باید انجام شود و تا کی.
              </p>
              <VRow>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.next_step"
                    label="گام بعدی"
                    placeholder="مثال: تماس سه‌شنبه برای هماهنگی جلسه با مدیر خرید"
                    rows="3"
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
                <VCol cols="12" class="d-flex flex-wrap gap-4">
                  <VBtn
                    color="primary"
                    :loading="saving"
                    @click="submit"
                  >
                    ثبت فرصت
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="activeTab = 'funnel'"
                  >
                    قبلی
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VWindowItem>

        <VWindowItem value="notes">
          <VCard title="یادداشت شروع">
            <VCardText>
              <p class="text-body-2 text-medium-emphasis mb-6">
                اختیاری — منبع سرنخ و نکات آزاد.
              </p>
              <VRow>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.lead_source_note"
                    label="منبع سرنخ / یادداشت شروع"
                    placeholder="نمایشگاه / تماس سرد / معرفی مشتری قبلی"
                    rows="3"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.opening_note"
                    label="یادداشت آزاد"
                    placeholder="هر نکته‌ای که بعداً کمک می‌کند"
                    rows="3"
                  />
                </VCol>
                <VCol cols="12" class="d-flex flex-wrap gap-4">
                  <VBtn
                    color="primary"
                    :loading="saving"
                    @click="submit"
                  >
                    ثبت فرصت
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="outlined"
                    @click="activeTab = 'step'"
                  >
                    قبلی
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VWindowItem>
      </VWindow>
    </VForm>
  </div>
</template>
