<script setup lang="ts">
import { useRouter } from 'vue-router'
import JalaliDateField from '@/components/forms/JalaliDateField.vue'
import PhoneField from '@/components/forms/PhoneField.vue'
import { useNotify } from '@/composables/useNotify'
import {
  createOpportunity,
  fetchOpportunityStages,
  getCompany,
} from '@/utils/api'
import { todayIso } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const router = useRouter()
const { success, error: notifyError } = useNotify()

const formRef = ref()
const saving = ref(false)
const stages = ref<{ title: string; value: number }[]>([])

const productItems = ['نوتوپیا', 'سایر محصولات چاپی', 'خدمات سفارشی']

const form = ref({
  organization_name: '',
  products: ['نوتوپیا'] as string[],
  industry: '',
  contact_name: '',
  contact_role: '',
  contact_mobile: '',
  contact_phone: '',
  contact_email: '',
  address: '',
  application_need: '',
  tirage: '',
  budget_band: '',
  purchase_timing: '',
  stage_id: null as number | null,
  next_step: '',
  next_step_due_on: todayIso() as string | null,
  opening_note: '',
})

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

  saving.value = true
  try {
    const res = await createOpportunity({
      organization_name: form.value.organization_name,
      products: form.value.products,
      industry: form.value.industry || null,
      contact_name: form.value.contact_name || null,
      contact_role: form.value.contact_role || null,
      contact_mobile: form.value.contact_mobile || null,
      contact_phone: form.value.contact_phone || null,
      contact_email: form.value.contact_email || null,
      address: form.value.address || null,
      application_need: form.value.application_need || null,
      tirage: form.value.tirage || null,
      budget_band: form.value.budget_band || null,
      purchase_timing: form.value.purchase_timing || null,
      stage_id: form.value.stage_id,
      next_step: form.value.next_step,
      next_step_due_on: form.value.next_step_due_on,
      opening_note: form.value.opening_note || null,
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
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-6">
      <div>
        <VBtn
          variant="text"
          color="secondary"
          size="small"
          class="mb-1"
          @click="$router.push('/opportunities')"
        >
          بازگشت به فرصت‌ها
        </VBtn>
        <h4 class="text-h4 mb-0">
          ثبت کامل فرصت
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          همه اطلاعات سازمان، مخاطب، نیاز و گام اول
        </p>
      </div>
    </div>

    <VForm
      ref="formRef"
      @submit.prevent="submit"
    >
      <VRow>
        <VCol cols="12" md="8">
          <VCard class="mb-4" title="هویت سازمان">
            <VCardText>
              <VRow>
                <VCol cols="12">
                  <VTextField
                    v-model="form.organization_name"
                    label="سازمان"
                    :rules="[rules.required('سازمان')]"
                  />
                </VCol>
                <VCol cols="12" sm="6">
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
                    v-model="form.industry"
                    label="صنعت / حوزه"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextField
                    v-model="form.address"
                    label="آدرس"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard class="mb-4" title="مخاطب">
            <VCardText>
              <VRow>
                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="form.contact_name"
                    label="نام مخاطب"
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="form.contact_role"
                    label="سمت"
                  />
                </VCol>
                <VCol cols="12" sm="4">
                  <PhoneField
                    v-model="form.contact_mobile"
                    label="موبایل"
                    mobile
                  />
                </VCol>
                <VCol cols="12" sm="4">
                  <PhoneField
                    v-model="form.contact_phone"
                    label="تلفن"
                  />
                </VCol>
                <VCol cols="12" sm="4">
                  <VTextField
                    v-model="form.contact_email"
                    label="ایمیل"
                    type="email"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard class="mb-4" title="نیاز و بودجه">
            <VCardText>
              <VRow>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.application_need"
                    label="شرح نیاز / کاربرد"
                    rows="3"
                  />
                </VCol>
                <VCol cols="12" sm="4">
                  <VTextField
                    v-model="form.tirage"
                    label="تیراژ تقریبی"
                  />
                </VCol>
                <VCol cols="12" sm="4">
                  <VTextField
                    v-model="form.budget_band"
                    label="بازه بودجه"
                  />
                </VCol>
                <VCol cols="12" sm="4">
                  <VTextField
                    v-model="form.purchase_timing"
                    label="زمان‌بندی خرید"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="4">
          <VCard class="mb-4" title="مرحله و گام اول">
            <VCardText>
              <VSelect
                v-model="form.stage_id"
                :items="stages"
                label="مرحله قیف"
                class="mb-3"
              />
              <VTextarea
                v-model="form.next_step"
                label="گام بعدی"
                rows="3"
                class="mb-3"
                :rules="[rules.required('گام بعدی')]"
              />
              <JalaliDateField
                v-model="form.next_step_due_on"
                label="تاریخ گام بعدی"
              />
              <VTextarea
                v-model="form.opening_note"
                label="یادداشت ابتدایی"
                rows="3"
                class="mt-3"
              />
            </VCardText>
            <VCardActions class="px-4 pb-4">
              <VBtn
                variant="text"
                color="secondary"
                @click="$router.push('/opportunities')"
              >
                انصراف
              </VBtn>
              <VSpacer />
              <VBtn
                color="primary"
                :loading="saving"
                @click="submit"
              >
                ثبت فرصت
              </VBtn>
            </VCardActions>
          </VCard>
        </VCol>
      </VRow>
    </VForm>
  </div>
</template>
