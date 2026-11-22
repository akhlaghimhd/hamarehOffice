<script setup lang="ts">
import { rules } from '@/utils/formRules'
const formRef = ref()
const name = ref('')
const email = ref('')
async function submit() {
  await formRef.value?.validate?.()
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="اعتبارسنجی فرم">
      <VCardText>
        <VForm ref="formRef" @submit.prevent="submit">
          <VRow>
            <VCol cols="12" md="6">
              <VTextField v-model="name" label="نام" :rules="[rules.required('نام')]" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="email" label="ایمیل" type="email" :rules="[rules.required('ایمیل')]" />
            </VCol>
            <VCol cols="12">
              <VBtn type="submit" color="primary">بررسی</VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </div>
</template>
