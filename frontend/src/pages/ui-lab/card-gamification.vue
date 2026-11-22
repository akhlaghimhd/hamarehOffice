<script setup lang="ts">
import trophy from '@images/misc/trophy.png'
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <p class="text-h5 mb-4">کارت گیمیفیکیشن</p>
    <VRow>
      <VCol cols="12" md="6">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <div class="text-h5 mb-1">سطح طلایی</div>
              <div class="text-body-2 text-medium-emphasis mb-3">۱۲۵۰ امتیاز تا سطح بعدی</div>
              <VProgressLinear model-value="72" color="warning" height="8" rounded class="mb-2" style="max-width: 220px" />
              <VChip color="warning" size="small" variant="tonal">رتبه ۳ فروش</VChip>
            </div>
            <VImg :src="trophy" width="90" />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard title="نشان‌ها">
          <VCardText class="d-flex flex-wrap gap-3">
            <VAvatar color="primary" variant="tonal" size="48"><VIcon icon="ri-medal-line" /></VAvatar>
            <VAvatar color="success" variant="tonal" size="48"><VIcon icon="ri-trophy-line" /></VAvatar>
            <VAvatar color="warning" variant="tonal" size="48"><VIcon icon="ri-star-line" /></VAvatar>
            <VAvatar color="info" variant="tonal" size="48"><VIcon icon="ri-flashlight-line" /></VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
