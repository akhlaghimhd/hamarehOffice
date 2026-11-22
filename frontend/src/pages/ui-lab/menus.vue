<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="منو">
      <VCardText class="d-flex gap-4">
        <VMenu>
          <template #activator="{ props }">
            <VBtn v-bind="props">منوی عملیات</VBtn>
          </template>
          <VList density="compact">
            <VListItem title="ویرایش" prepend-icon="ri-pencil-line" />
            <VListItem title="بستن گام" prepend-icon="ri-check-line" />
            <VDivider />
            <VListItem title="حذف" prepend-icon="ri-delete-bin-line" base-color="error" />
          </VList>
        </VMenu>
      </VCardText>
    </VCard>
  </div>
</template>
