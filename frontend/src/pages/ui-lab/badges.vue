<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="بج">
      <VCardText class="d-flex flex-wrap align-center gap-6">
        <VBadge content="۳" color="error">
          <VIcon icon="ri-notification-3-line" size="24" />
        </VBadge>
        <VBadge content="۹۹+" color="primary">
          <VBtn icon variant="tonal"><VIcon icon="ri-mail-line" /></VBtn>
        </VBadge>
        <VBadge color="success" dot>
          <VAvatar color="secondary">ن</VAvatar>
        </VBadge>
        <VBadge content="جدید" color="info" inline>
          <span class="text-body-1">وضعیت</span>
        </VBadge>
      </VCardText>
    </VCard>
  </div>
</template>
