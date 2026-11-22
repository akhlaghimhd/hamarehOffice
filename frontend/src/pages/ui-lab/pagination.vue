<script setup lang="ts">
const page = ref(1)
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="صفحه‌بندی">
      <VCardText class="d-flex flex-column gap-6">
        <VPagination v-model="page" :length="8" />
        <VPagination v-model="page" :length="8" density="compact" total-visible="5" />
      </VCardText>
    </VCard>
  </div>
</template>
