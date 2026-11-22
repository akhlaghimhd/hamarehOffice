<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="پیشرفت خطی">
      <VCardText class="d-flex flex-column gap-4">
        <VProgressLinear indeterminate color="primary" />
        <VProgressLinear :model-value="65" color="success" height="8" rounded />
        <VProgressLinear :model-value="30" color="warning" height="10" rounded>
          <template #default="{ value }">{{ Math.ceil(value) }}٪</template>
        </VProgressLinear>
      </VCardText>
    </VCard>
  </div>
</template>
