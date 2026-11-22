<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="تایم‌لاین (نسخه ساده)">
      <VCardText>
        <VTimeline density="compact" side="end">
          <VTimelineItem dot-color="primary" size="small">
            <div class="text-body-2 font-weight-medium">ثبت فرصت</div>
            <div class="text-caption text-medium-emphasis">امروز</div>
          </VTimelineItem>
          <VTimelineItem dot-color="success" size="small">
            <div class="text-body-2 font-weight-medium">تماس اولیه</div>
            <div class="text-caption text-medium-emphasis">دیروز</div>
          </VTimelineItem>
          <VTimelineItem dot-color="warning" size="small">
            <div class="text-body-2 font-weight-medium">ارسال پیشنهاد</div>
            <div class="text-caption text-medium-emphasis">هفته قبل</div>
          </VTimelineItem>
        </VTimeline>
      </VCardText>
    </VCard>
  </div>
</template>
