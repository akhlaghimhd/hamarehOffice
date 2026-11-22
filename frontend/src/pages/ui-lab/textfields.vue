<script setup lang="ts">
const basic = ref('')
const withIcon = ref('')
const error = ref('مقدار نامعتبر')
</script>

<template>
  <div>
    <VBtn
      variant="text"
      color="secondary"
      size="small"
      class="px-0 mb-4"
      prepend-icon="ri-arrow-right-line"
      :to="{ path: '/ui-lab' }"
    >
      الگوهای UI
    </VBtn>

    <VCard title="Text Field — الگوی فرم پروژه">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VTextField v-model="basic" label="برچسب" placeholder="متن راهنما" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField
              v-model="withIcon"
              label="با آیکن"
              prepend-inner-icon="ri-mail-line"
              placeholder="email@example.com"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField label="فقط خواندنی" model-value="ثابت" readonly />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField label="غیرفعال" disabled model-value="غیرفعال" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField
              v-model="error"
              label="خطا"
              error
              error-messages="این فیلد الزامی است"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VTextField
              label="تراکم compact"
              density="compact"
              placeholder="حالت فشرده"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
