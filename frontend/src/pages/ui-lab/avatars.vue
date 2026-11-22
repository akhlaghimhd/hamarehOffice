<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="آواتار">
      <VCardText class="d-flex flex-wrap align-center gap-4">
        <VAvatar color="primary"><span class="text-white">م</span></VAvatar>
        <VAvatar color="success" variant="tonal">ا</VAvatar>
        <VAvatar color="info"><VIcon icon="ri-user-line" /></VAvatar>
        <VAvatar size="40" color="warning" variant="tonal">ک</VAvatar>
        <VAvatar size="56" rounded="lg" color="primary" variant="tonal"><VIcon icon="ri-building-2-line" /></VAvatar>
        <VBadge color="success" dot location="bottom end" offset-x="2" offset-y="2">
          <VAvatar color="secondary">آنلاین</VAvatar>
        </VBadge>
      </VCardText>
    </VCard>
  </div>
</template>
