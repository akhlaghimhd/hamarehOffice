<script setup lang="ts">
const show = ref(true)
const loading = ref(false)
function refresh() {
  loading.value = true
  setTimeout(() => { loading.value = false }, 1000)
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <p class="text-h5 mb-4">اکشن روی کارت</p>
    <VRow>
      <VCol cols="12" md="6">
        <VCard v-if="show">
          <VCardItem>
            <VCardTitle>کارت با منوی اکشن</VCardTitle>
            <template #append>
              <VBtn icon variant="text" size="small" @click="refresh">
                <VIcon icon="ri-refresh-line" />
              </VBtn>
              <VBtn icon variant="text" size="small" @click="show = false">
                <VIcon icon="ri-close-line" />
              </VBtn>
            </template>
          </VCardItem>
          <VCardText>
            <VProgressLinear v-if="loading" indeterminate color="primary" class="mb-3" />
            محتوای نمونه کارت. با بستن از صفحه حذف می‌شود.
          </VCardText>
          <VCardActions>
            <VBtn variant="text" color="secondary">انصراف</VBtn>
            <VSpacer />
            <VBtn color="primary">تأیید</VBtn>
          </VCardActions>
        </VCard>
        <VBtn v-else variant="tonal" @click="show = true">نمایش مجدد کارت</VBtn>
      </VCol>
    </VRow>
  </div>
</template>
