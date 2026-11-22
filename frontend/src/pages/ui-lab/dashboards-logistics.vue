<script setup lang="ts">
import VueApexCharts from 'vue3-apexcharts'
import '@/assets/styles/ui-lab-motion.css'

const boyStand = '/illustrations/boy-stand.png'
const girlTalk = '/illustrations/girl-talk.png'

const fleet = [
  { title: 'در مسیر', value: '۱۸', color: 'primary', icon: 'ri-truck-line' },
  { title: 'تحویل‌شده', value: '۱۴۲', color: 'success', icon: 'ri-checkbox-circle-line' },
  { title: 'تأخیر', value: '۳', color: 'error', icon: 'ri-time-line' },
  { title: 'انبار', value: '۹۶٪', color: 'info', icon: 'ri-home-gear-line' },
]

const routeSeries = [{ name: 'محموله', data: [12, 18, 14, 22, 16, 25, 20] }]
const routeOptions = {
  chart: { toolbar: { show: false }, animations: { enabled: true, speed: 750 } },
  stroke: { curve: 'smooth', width: 3 },
  colors: ['#16B1FF'],
  fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
  dataLabels: { enabled: false },
  xaxis: { categories: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] },
}

const shipments = [
  { code: 'SH-۱۰۲4', dest: 'تهران', status: 'در مسیر', color: 'primary' },
  { code: 'SH-۱۰۱۹', dest: 'اصفهان', status: 'تحویل', color: 'success' },
  { code: 'SH-۱۰۱۱', dest: 'شیراز', status: 'تأخیر', color: 'error' },
]
</script>

<template>
  <div>
    <div class="mb-6 lab-animate">
      <VBtn variant="text" color="secondary" size="small" class="px-0 mb-1" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
      <h4 class="text-h4 mb-0">داشبورد لجستیک</h4>
      <p class="text-body-2 text-medium-emphasis mb-0">ناوگان، محموله و انبار</p>
    </div>

    <VRow>
      <VCol
        v-for="(f, i) in fleet"
        :key="f.title"
        cols="6"
        md="3"
        class="lab-animate"
        :class="`lab-delay-${i + 1}`"
      >
        <VCard>
          <VCardText class="d-flex align-center gap-3">
            <VAvatar :color="f.color" variant="tonal" rounded>
              <VIcon :icon="f.icon" />
            </VAvatar>
            <div>
              <div class="text-caption text-medium-emphasis">{{ f.title }}</div>
              <div class="text-h5">{{ f.value }}</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="8" class="lab-animate lab-delay-3">
        <VCard title="محموله‌های هفته">
          <VCardText>
            <VueApexCharts type="area" height="280" :options="routeOptions" :series="routeSeries" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4" class="lab-animate lab-delay-4">
        <VCard class="lab-hero-card mb-4">
          <VCardText class="text-center py-6">
            <img :src="boyStand" class="lab-float mb-2" width="120" height="120" alt="">
            <div class="text-h6">وضعیت ناوگان</div>
            <div class="text-body-2 text-medium-emphasis">۱۸ خودرو فعال</div>
          </VCardText>
        </VCard>
        <VCard>
          <VCardText class="d-flex align-center gap-3">
            <img :src="girlTalk" width="72" height="72" alt="" class="lab-float">
            <div>
              <div class="text-body-1 font-weight-medium">هماهنگی انبار</div>
              <div class="text-caption text-medium-emphasis">۳ درخواست در صف</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" class="lab-animate lab-delay-5">
        <VCard title="آخرین محموله‌ها">
          <VList>
            <VListItem
              v-for="s in shipments"
              :key="s.code"
              :title="s.code"
              :subtitle="s.dest"
            >
              <template #append>
                <VChip size="small" :color="s.color" variant="tonal">{{ s.status }}</VChip>
              </template>
            </VListItem>
          </VList>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
