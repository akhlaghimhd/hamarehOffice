<script setup lang="ts">
import trophy from '@images/misc/trophy.png'
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <p class="text-h5 mb-4">کارت پیشرفته</p>
    <VRow>
      <VCol cols="12" md="6">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <div class="flex-grow-1">
              <div class="text-h5 mb-1">تبریک 🎉</div>
              <div class="text-body-2 text-medium-emphasis mb-4">بهترین فروشنده ماه</div>
              <div class="text-h4 text-primary mb-1">۴۲٫۰ م</div>
              <VBtn size="small" color="primary">مشاهده فروش</VBtn>
            </div>
            <VImg :src="trophy" width="100" />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard title="فعالیت‌های اخیر">
          <VList density="compact">
            <VListItem title="فرصت جدید ثبت شد" subtitle="۲ دقیقه پیش" prepend-icon="ri-funds-box-line" />
            <VListItem title="پیشنهاد ارسال شد" subtitle="۱ ساعت پیش" prepend-icon="ri-mail-send-line" />
            <VListItem title="جلسه هماهنگ شد" subtitle="دیروز" prepend-icon="ri-calendar-check-line" />
          </VList>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard color="primary">
          <VCardText class="text-white">
            <div class="text-body-2 mb-1 opacity-80">درآمد کل</div>
            <div class="text-h4 mb-2">۱۸۶ م</div>
            <div class="text-caption">+۱۸٪ نسبت به فصل قبل</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard color="success">
          <VCardText class="text-white">
            <div class="text-body-2 mb-1 opacity-80">بردها</div>
            <div class="text-h4 mb-2">۲۷</div>
            <div class="text-caption">این ماه</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard color="warning">
          <VCardText>
            <div class="text-body-2 mb-1">نیاز به پیگیری</div>
            <div class="text-h4 mb-2">۹</div>
            <div class="text-caption">گام سررسید گذشته</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
