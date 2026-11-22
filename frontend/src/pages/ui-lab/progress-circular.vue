<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="پیشرفت دایره‌ای">
      <VCardText class="d-flex flex-wrap gap-6 align-center">
        <VProgressCircular indeterminate color="primary" />
        <VProgressCircular :model-value="70" color="success" />
        <VProgressCircular :model-value="40" color="warning" size="48" width="6">۴۰٪</VProgressCircular>
      </VCardText>
    </VCard>
  </div>
</template>
