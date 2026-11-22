<script setup lang="ts">
const step = ref(1)
const form = ref({ org: '', contact: '', need: '', next: '' })

function next() {
  if (step.value < 4)
    step.value++
}
function prev() {
  if (step.value > 1)
    step.value--
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>

    <VCard title="ویزارد فرم (شماره‌دار)">
      <VCardText>
        <VStepper v-model="step" alt-labels class="mb-6">
          <VStepperHeader>
            <VStepperItem :value="1" title="هویت" />
            <VDivider />
            <VStepperItem :value="2" title="تماس" />
            <VDivider />
            <VStepperItem :value="3" title="نیاز" />
            <VDivider />
            <VStepperItem :value="4" title="گام بعد" />
          </VStepperHeader>
        </VStepper>

        <VWindow v-model="step" :touch="false">
          <VWindowItem :value="1">
            <VTextField v-model="form.org" label="سازمان" class="mb-4" />
          </VWindowItem>
          <VWindowItem :value="2">
            <VTextField v-model="form.contact" label="نام مخاطب" class="mb-4" />
          </VWindowItem>
          <VWindowItem :value="3">
            <VTextField v-model="form.need" label="نیاز / کاربرد" class="mb-4" />
          </VWindowItem>
          <VWindowItem :value="4">
            <VTextarea v-model="form.next" label="گام بعدی" rows="3" class="mb-4" />
          </VWindowItem>
        </VWindow>

        <div class="d-flex gap-3">
          <VBtn color="secondary" variant="outlined" :disabled="step === 1" @click="prev">قبلی</VBtn>
          <VSpacer />
          <VBtn v-if="step < 4" color="primary" @click="next">ادامه</VBtn>
          <VBtn v-else color="primary">ثبت</VBtn>
        </div>
      </VCardText>
    </VCard>

    <VCard title="ویزارد با آیکن" class="mt-6">
      <VCardText>
        <VTabs v-model="step" class="v-tabs-pill mb-6" show-arrows>
          <VTab :value="1"><VIcon start icon="ri-building-2-line" size="18" />هویت</VTab>
          <VTab :value="2"><VIcon start icon="ri-user-line" size="18" />تماس</VTab>
          <VTab :value="3"><VIcon start icon="ri-funds-line" size="18" />نیاز</VTab>
          <VTab :value="4"><VIcon start icon="ri-flag-line" size="18" />گام</VTab>
        </VTabs>
        <div class="text-body-2 text-medium-emphasis">
          الگوی تب‌کپسولی برای فرم چندمرحله‌ای (همان سبک ثبت کامل فرصت).
        </div>
      </VCardText>
    </VCard>
  </div>
</template>
