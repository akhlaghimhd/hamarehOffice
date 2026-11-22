<script setup lang="ts">
const pill = ref('one')
const standard = ref('a')
</script>

<template>
  <div>
    <VBtn
      variant="text"
      color="secondary"
      size="small"
      class="px-0 mb-4"
      prepend-icon="ri-arrow-right-line"
      :to="{ path: '/ui-lab' }"
    >
      الگوهای UI
    </VBtn>

    <VRow>
      <VCol cols="12">
        <VCard title="تب کپسولی (مرجع Account / فرم کامل)">
          <VCardText>
            <VTabs v-model="pill" class="v-tabs-pill" show-arrows>
              <VTab value="one"><VIcon start icon="ri-user-line" size="18" />حساب</VTab>
              <VTab value="two"><VIcon start icon="ri-lock-line" size="18" />امنیت</VTab>
              <VTab value="three"><VIcon start icon="ri-notification-3-line" size="18" />اعلان</VTab>
            </VTabs>
            <VWindow v-model="pill" class="mt-5" :touch="false">
              <VWindowItem value="one">
                <VCard title="جزئیات حساب" subtitle="نمونه محتوای تب اول">
                  <VCardText>
                    الگوی استاندارد فرم‌های چندبخشی پروژه.
                  </VCardText>
                </VCard>
              </VWindowItem>
              <VWindowItem value="two">محتوای امنیت</VWindowItem>
              <VWindowItem value="three">محتوای اعلان</VWindowItem>
            </VWindow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12">
        <VCard title="تب استاندارد">
          <VTabs v-model="standard" color="primary">
            <VTab value="a">آیتم یک</VTab>
            <VTab value="b">آیتم دو</VTab>
            <VTab value="c">آیتم سه</VTab>
          </VTabs>
          <VCardText>
            <VWindow v-model="standard" :touch="false">
              <VWindowItem value="a">محتوای یک</VWindowItem>
              <VWindowItem value="b">محتوای دو</VWindowItem>
              <VWindowItem value="c">محتوای سه</VWindowItem>
            </VWindow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
