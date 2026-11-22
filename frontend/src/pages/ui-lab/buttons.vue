<script setup lang="ts">
const loading = ref(false)
function fakeLoad() {
  loading.value = true
  setTimeout(() => { loading.value = false }, 1500)
}
</script>

<template>
  <div>
    <div class="d-flex align-center gap-2 mb-6">
      <VBtn
        variant="text"
        color="secondary"
        size="small"
        class="px-0"
        prepend-icon="ri-arrow-right-line"
        :to="{ path: '/ui-lab' }"
      >
        الگوهای UI
      </VBtn>
    </div>

    <VRow>
      <VCol cols="12" md="6">
        <VCard title="رنگ و variant">
          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn color="primary">Primary</VBtn>
            <VBtn color="secondary">Secondary</VBtn>
            <VBtn color="success">Success</VBtn>
            <VBtn color="info">Info</VBtn>
            <VBtn color="warning">Warning</VBtn>
            <VBtn color="error">Error</VBtn>
          </VCardText>
          <VDivider />
          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn variant="tonal">Tonal</VBtn>
            <VBtn variant="outlined">Outlined</VBtn>
            <VBtn variant="text">Text</VBtn>
            <VBtn variant="plain">Plain</VBtn>
            <VBtn variant="elevated">Elevated</VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard title="اندازه و وضعیت">
          <VCardText class="d-flex flex-wrap align-center gap-4">
            <VBtn size="x-small">XS</VBtn>
            <VBtn size="small">Small</VBtn>
            <VBtn size="default">Default</VBtn>
            <VBtn size="large">Large</VBtn>
          </VCardText>
          <VDivider />
          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn
              :loading="loading"
              @click="fakeLoad"
            >
              Loading
            </VBtn>
            <VBtn disabled>
              Disabled
            </VBtn>
            <VBtn
              color="primary"
              prepend-icon="ri-save-3-line"
            >
              با آیکن
            </VBtn>
            <VBtn
              color="error"
              variant="outlined"
              append-icon="ri-delete-bin-line"
            >
              حذف
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12">
        <VCard title="الگوی اکشن فرم (مرجع پروژه)">
          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn color="primary">ثبت / ذخیره</VBtn>
            <VBtn color="secondary" variant="outlined">انصراف</VBtn>
            <VBtn color="error" variant="tonal">حذف</VBtn>
            <VBtn color="info" variant="tonal">پیش‌نمایش</VBtn>
          </VCardText>
          <VCardText class="text-body-2 text-medium-emphasis pt-0">
            اولویت: primary = عمل اصلی · outlined secondary = انصراف · tonal error = مخرب · tonal info = کمکی
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
