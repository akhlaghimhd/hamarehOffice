<script setup lang="ts">
const show = ref(false)
const color = ref('success')
const text = ref('')
function open(c: string, t: string) {
  color.value = c
  text.value = t
  show.value = true
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="اسنک‌بار / پیام (مرجع useNotify)">
      <VCardText class="d-flex flex-wrap gap-3">
        <VBtn color="success" @click="open('success', 'عملیات موفق بود')">موفق</VBtn>
        <VBtn color="error" @click="open('error', 'خطا در ثبت')">خطا</VBtn>
        <VBtn color="info" @click="open('info', 'پیام اطلاع‌رسانی')">اطلاع</VBtn>
        <VBtn color="warning" @click="open('warning', 'هشدار')">هشدار</VBtn>
      </VCardText>
    </VCard>
    <VSnackbar v-model="show" :color="color" location="top" multi-line timeout="3000">
      {{ text }}
      <template #actions>
        <VBtn variant="text" @click="show = false">بستن</VBtn>
      </template>
    </VSnackbar>
  </div>
</template>
