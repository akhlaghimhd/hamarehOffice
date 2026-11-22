<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="لیست">
      <VList>
        <VListItem title="فرصت OPP-001" subtitle="شرکت نمونه" prepend-icon="ri-funds-box-line">
          <template #append><VChip size="x-small" color="success" variant="tonal">فعال</VChip></template>
        </VListItem>
        <VListItem title="فرصت OPP-002" subtitle="سازمان دوم" prepend-icon="ri-funds-box-line">
          <template #append><VChip size="x-small" color="warning" variant="tonal">متوقف</VChip></template>
        </VListItem>
        <VListItem title="طرف تجاری" subtitle="مشتری" prepend-icon="ri-user-star-line" />
      </VList>
    </VCard>
  </div>
</template>
