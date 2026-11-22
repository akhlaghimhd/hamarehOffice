<script setup lang="ts">
const stats = [
  { title: 'فروش ماه', value: '۱۲٫۴ م', change: '+۱۲٪', color: 'primary', icon: 'ri-money-dollar-circle-line' },
  { title: 'فرصت‌های باز', value: '۴۸', change: '+۸٪', color: 'success', icon: 'ri-funds-box-line' },
  { title: 'نرخ تبدیل', value: '۲۴٪', change: '-۲٪', color: 'warning', icon: 'ri-pie-chart-2-line' },
  { title: 'مشتریان جدید', value: '۱۹', change: '+۵٪', color: 'info', icon: 'ri-user-add-line' },
]
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>

    <p class="text-h5 mb-4">کارت آمار</p>
    <VRow>
      <VCol v-for="s in stats" :key="s.title" cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar :color="s.color" variant="tonal" rounded size="42">
              <VIcon :icon="s.icon" />
            </VAvatar>
            <div>
              <div class="text-body-2 text-medium-emphasis">{{ s.title }}</div>
              <div class="text-h5">{{ s.value }}</div>
              <div class="text-caption" :class="s.change.startsWith('+') ? 'text-success' : 'text-error'">{{ s.change }} نسبت به ماه قبل</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <p class="text-h5 mb-4 mt-8">آمار با نوار پیشرفت</p>
    <VRow>
      <VCol cols="12" md="4">
        <VCard title="تحقق هدف فروش">
          <VCardText>
            <div class="text-h4 mb-2">۶۸٪</div>
            <VProgressLinear model-value="68" color="primary" height="8" rounded class="mb-2" />
            <div class="text-body-2 text-medium-emphasis">هدف ماه: ۸۰۰ میلیون ریال</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard title="پاسخ‌گویی سرنخ">
          <VCardText>
            <div class="text-h4 mb-2">۸۲٪</div>
            <VProgressLinear model-value="82" color="success" height="8" rounded class="mb-2" />
            <div class="text-body-2 text-medium-emphasis">کمتر از ۲۴ ساعت</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard title="رضایت مشتری">
          <VCardText>
            <div class="text-h4 mb-2">۹۱٪</div>
            <VProgressLinear model-value="91" color="info" height="8" rounded class="mb-2" />
            <div class="text-body-2 text-medium-emphasis">بر اساس نظرسنجی</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
