<script setup lang="ts">
const selected = ref(['نوتوپیا'])
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VRow>
      <VCol cols="12" md="6">
        <VCard title="چیپ">
          <VCardText class="d-flex flex-wrap gap-2">
            <VChip>پیش‌فرض</VChip>
            <VChip color="primary">Primary</VChip>
            <VChip color="success" variant="tonal">موفق</VChip>
            <VChip color="error" variant="outlined" closable>قابل بستن</VChip>
            <VChip color="info" prepend-icon="ri-user-line">با آیکن</VChip>
            <VChip size="small" color="warning">کوچک</VChip>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard title="انتخاب چندتایی (مثل محصول فرصت)">
          <VCardText>
            <VSelect
              v-model="selected"
              :items="['نوتوپیا', 'چاپی', 'سفارشی']"
              label="محصول"
              multiple
              chips
              closable-chips
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
