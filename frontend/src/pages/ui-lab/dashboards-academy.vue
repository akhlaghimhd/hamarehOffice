<script setup lang="ts">
import VueApexCharts from 'vue3-apexcharts'
import '@/assets/styles/ui-lab-motion.css'

const girlLaptop = '/illustrations/girl-laptop.png'
const boyCoffee = '/illustrations/boy-coffee.png'

const courses = [
  { title: 'آشنایی با قیف فروش', progress: 80, students: 42 },
  { title: 'پیکربندی نوتوپیا', progress: 55, students: 28 },
  { title: 'مهارت مذاکره', progress: 30, students: 19 },
]

const progressSeries = [70]
const progressOptions = {
  chart: { sparkline: { enabled: true }, animations: { enabled: true } },
  colors: ['#8C57FF'],
  plotOptions: {
    radialBar: {
      hollow: { size: '60%' },
      dataLabels: { name: { show: false }, value: { fontSize: '22px', offsetY: 6 } },
    },
  },
  labels: ['پیشرفت'],
}
</script>

<template>
  <div>
    <div class="mb-6 lab-animate">
      <VBtn variant="text" color="secondary" size="small" class="px-0 mb-1" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
      <h4 class="text-h4 mb-0">داشبورد آموزش</h4>
      <p class="text-body-2 text-medium-emphasis mb-0">دوره‌ها و پیشرفت یادگیری</p>
    </div>

    <VRow>
      <VCol cols="12" md="7" class="lab-animate lab-delay-1">
        <VCard class="lab-hero-card">
          <VCardText class="d-flex align-center justify-space-between gap-4 py-6">
            <div>
              <div class="text-h5 mb-1">ادامه یادگیری 📚</div>
              <div class="text-body-2 text-medium-emphasis mb-3">۲ درس باقی‌مانده تا اتمام دوره جاری</div>
              <VBtn color="primary" size="small">ادامه دوره</VBtn>
            </div>
            <img :src="girlLaptop" class="lab-float" width="150" height="150" alt="">
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="5" class="lab-animate lab-delay-2">
        <VCard class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VueApexCharts type="radialBar" height="140" width="140" :options="progressOptions" :series="progressSeries" />
            <div>
              <div class="text-h6">پیشرفت کلی</div>
              <div class="text-body-2 text-medium-emphasis">۷ دوره فعال در تیم</div>
              <VChip class="mt-2" size="small" color="success" variant="tonal">+۳ این هفته</VChip>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="8" class="lab-animate lab-delay-3">
        <VCard title="دوره‌های من">
          <VCardText class="d-flex flex-column gap-5">
            <div v-for="c in courses" :key="c.title">
              <div class="d-flex justify-space-between mb-1">
                <span class="font-weight-medium">{{ c.title }}</span>
                <span class="text-caption text-medium-emphasis">{{ c.students }} نفر</span>
              </div>
              <VProgressLinear :model-value="c.progress" color="primary" height="8" rounded />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4" class="lab-animate lab-delay-4">
        <VCard class="h-100 text-center">
          <VCardText class="py-8">
            <img :src="boyCoffee" class="lab-float mb-3" width="120" height="120" alt="">
            <div class="text-h6 mb-1">مربی همراه</div>
            <div class="text-body-2 text-medium-emphasis mb-4">سؤال دارید؟ از راهنمای داخلی بپرسید.</div>
            <VBtn variant="tonal" color="primary" size="small">گفتگو</VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
