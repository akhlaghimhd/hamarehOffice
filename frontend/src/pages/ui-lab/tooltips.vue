<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="تولتیپ">
      <VCardText class="d-flex flex-wrap gap-4">
        <VBtn v-tooltip="'راهنمای کوتاه'">هاور کنید</VBtn>
        <VBtn v-tooltip:bottom="'پایین'">پایین</VBtn>
        <VBtn v-tooltip:start="'شروع'">شروع (RTL)</VBtn>
      </VCardText>
    </VCard>
  </div>
</template>
