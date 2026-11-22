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
      <VCol cols="12" md="6">
        <VCard title="نوع هشدار">
          <VCardText class="d-flex flex-column gap-4">
            <VAlert type="success" title="موفق">عملیات با موفقیت انجام شد.</VAlert>
            <VAlert type="info" title="اطلاع">اطلاعات تکمیلی برای کاربر.</VAlert>
            <VAlert type="warning" title="هشدار">قبل از ادامه بررسی کنید.</VAlert>
            <VAlert type="error" title="خطا">ثبت انجام نشد؛ دوباره تلاش کنید.</VAlert>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard title="variant">
          <VCardText class="d-flex flex-column gap-4">
            <VAlert type="primary" variant="tonal" title="Tonal">مناسب داخل کارت فرم</VAlert>
            <VAlert type="secondary" variant="outlined" title="Outlined">حاشیه بدون پس‌زمینه قوی</VAlert>
            <VAlert type="info" density="compact">فشرده برای راهنمای کوتاه فیلد</VAlert>
            <VAlert type="success" closable title="قابل بستن">با دکمه بستن</VAlert>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
