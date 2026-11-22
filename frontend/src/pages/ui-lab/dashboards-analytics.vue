<script setup lang="ts">
import VueApexCharts from 'vue3-apexcharts'
import '@/assets/styles/ui-lab-motion.css'

const girlLaptop = '/illustrations/girl-laptop.png'
const boyCoffee = '/illustrations/boy-coffee.png'

const stats = [
  { title: 'بازدید', value: '۲۴٫۸ه', change: '+۱۲٪', icon: 'ri-eye-line', color: 'primary' },
  { title: 'فروش', value: '۱٫۲ م', change: '+۸٪', icon: 'ri-shopping-cart-line', color: 'success' },
  { title: 'تبدیل', value: '۴٫۲٪', change: '-۱٪', icon: 'ri-pie-chart-2-line', color: 'warning' },
  { title: 'کاربر فعال', value: '۸۴۲', change: '+۵٪', icon: 'ri-user-heart-line', color: 'info' },
]

const revenueSeries = [{ name: 'درآمد', data: [28, 40, 36, 52, 38, 60, 55, 70, 66, 80, 75, 90] }]
const revenueOptions = {
  chart: { toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
  stroke: { curve: 'smooth', width: 3 },
  colors: ['#8C57FF'],
  fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
  dataLabels: { enabled: false },
  xaxis: { categories: ['فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا', 'آذر', 'دی', 'بهم', 'اسف'] },
  grid: { borderColor: 'rgba(var(--v-border-color), var(--v-border-opacity))' },
}

const trafficSeries = [45, 30, 15, 10]
const trafficOptions = {
  labels: ['جستجو', 'مستقیم', 'شبکه اجتماعی', 'ایمیل'],
  colors: ['#8C57FF', '#16B1FF', '#56CA00', '#FFB400'],
  legend: { position: 'bottom' },
  chart: { animations: { enabled: true } },
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-6 lab-animate">
      <div>
        <VBtn variant="text" color="secondary" size="small" class="px-0 mb-1" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
        <h4 class="text-h4 mb-0">داشبورد آنالیتیکس</h4>
        <p class="text-body-2 text-medium-emphasis mb-0">نمای کلی عملکرد با انیمیشن ورودی</p>
      </div>
    </div>

    <VRow>
      <!-- Hero -->
      <VCol cols="12" md="8" class="lab-animate lab-delay-1">
        <VCard class="lab-hero-card">
          <VCardText class="d-flex flex-wrap align-center justify-space-between gap-4 py-6">
            <div style="max-width: 360px">
              <div class="text-h5 mb-1">تبریک 🎉 مدیر</div>
              <div class="text-body-2 text-medium-emphasis mb-4">
                فروش این ماه ۱۸٪ بهتر از هدف است. گزارش کامل را ببینید.
              </div>
              <div class="text-h4 text-primary mb-1">۴۲٫۸ م</div>
              <VBtn color="primary" class="mt-2" size="small">مشاهده فروش</VBtn>
            </div>
            <img :src="girlLaptop" alt="" class="lab-hero-illu lab-float" width="160" height="160">
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4" class="lab-animate lab-delay-2">
        <VCard class="h-100">
          <VCardText class="d-flex flex-column align-center text-center py-6">
            <img :src="boyCoffee" alt="" class="lab-float mb-3" width="120" height="120">
            <div class="text-h6 mb-1">هدف فصل</div>
            <div class="text-body-2 text-medium-emphasis mb-3">۶۸٪ مسیر طی شده</div>
            <VProgressCircular :model-value="68" color="primary" size="72" width="6">۶۸٪</VProgressCircular>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Stats -->
      <VCol
        v-for="(s, i) in stats"
        :key="s.title"
        cols="12"
        sm="6"
        md="3"
        class="lab-animate"
        :class="`lab-delay-${i + 3}`"
      >
        <VCard>
          <VCardText class="d-flex align-center gap-3">
            <VAvatar :color="s.color" variant="tonal" rounded size="42">
              <VIcon :icon="s.icon" />
            </VAvatar>
            <div>
              <div class="text-body-2 text-medium-emphasis">{{ s.title }}</div>
              <div class="text-h5">{{ s.value }}</div>
              <div class="text-caption" :class="s.change.startsWith('+') ? 'text-success' : 'text-error'">{{ s.change }}</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="8" class="lab-animate lab-delay-4">
        <VCard title="روند درآمد سال">
          <VCardText>
            <VueApexCharts type="area" height="300" :options="revenueOptions" :series="revenueSeries" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4" class="lab-animate lab-delay-5">
        <VCard title="منابع ترافیک">
          <VCardText>
            <VueApexCharts type="donut" height="300" :options="trafficOptions" :series="trafficSeries" />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
