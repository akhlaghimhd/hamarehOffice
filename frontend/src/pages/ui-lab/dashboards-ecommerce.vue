<script setup lang="ts">
import VueApexCharts from 'vue3-apexcharts'
import '@/assets/styles/ui-lab-motion.css'

const girlTalk = '/illustrations/girl-talk.png'
const boyStand = '/illustrations/boy-stand.png'

const kpis = [
  { title: 'سفارش‌ها', value: '۱٬۲۴۸', icon: 'ri-shopping-bag-3-line', color: 'primary' },
  { title: 'درآمد', value: '۸۶٫۴ م', icon: 'ri-money-dollar-circle-line', color: 'success' },
  { title: 'میانگین سبد', value: '۶۹۰ه', icon: 'ri-bank-card-line', color: 'info' },
  { title: 'مرجوعی', value: '۲٫۱٪', icon: 'ri-arrow-go-back-line', color: 'warning' },
]

const salesSeries = [{ name: 'فروش', data: [40, 55, 48, 70, 62, 85, 78] }]
const salesOptions = {
  chart: { toolbar: { show: false }, animations: { enabled: true, speed: 700 } },
  plotOptions: { bar: { borderRadius: 8, columnWidth: '42%' } },
  colors: ['#8C57FF'],
  dataLabels: { enabled: false },
  xaxis: { categories: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] },
  grid: { borderColor: 'rgba(var(--v-border-color), var(--v-border-opacity))' },
}

const products = [
  { name: 'نوتوپیا کلاسیک', sold: 128, stock: 42 },
  { name: 'پکیج سازمانی', sold: 86, stock: 15 },
  { name: 'کاور ویژه', sold: 64, stock: 90 },
]
</script>

<template>
  <div>
    <div class="mb-6 lab-animate">
      <VBtn variant="text" color="secondary" size="small" class="px-0 mb-1" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
      <h4 class="text-h4 mb-0">داشبورد فروشگاهی</h4>
      <p class="text-body-2 text-medium-emphasis mb-0">سفارش، سبد و موجودی</p>
    </div>

    <VRow>
      <VCol cols="12" md="5" class="lab-animate lab-delay-1">
        <VCard class="lab-hero-card h-100">
          <VCardText class="d-flex align-center justify-space-between gap-3 py-6">
            <div>
              <div class="text-h5 mb-1">پرفروش امروز 🛍️</div>
              <div class="text-body-2 text-medium-emphasis mb-3">۳۴ سفارش جدید از صبح</div>
              <VBtn color="primary" size="small">مدیریت سفارش</VBtn>
            </div>
            <img :src="girlTalk" class="lab-float" width="130" height="130" alt="">
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="7" class="lab-animate lab-delay-2">
        <VRow dense>
          <VCol v-for="k in kpis" :key="k.title" cols="6">
            <VCard>
              <VCardText class="d-flex align-center gap-3">
                <VAvatar :color="k.color" variant="tonal" rounded>
                  <VIcon :icon="k.icon" />
                </VAvatar>
                <div>
                  <div class="text-caption text-medium-emphasis">{{ k.title }}</div>
                  <div class="text-h6">{{ k.value }}</div>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCol>

      <VCol cols="12" md="8" class="lab-animate lab-delay-3">
        <VCard title="فروش هفته">
          <VCardText>
            <VueApexCharts type="bar" height="280" :options="salesOptions" :series="salesSeries" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4" class="lab-animate lab-delay-4">
        <VCard>
          <VCardText class="text-center pb-2">
            <img :src="boyStand" class="lab-float mb-2" width="110" height="110" alt="">
            <div class="text-h6">پشتیبانی فروش</div>
            <div class="text-body-2 text-medium-emphasis mb-3">۹۲٪ رضایت خریدار</div>
          </VCardText>
          <VList density="compact">
            <VListItem
              v-for="p in products"
              :key="p.name"
              :title="p.name"
              :subtitle="`${p.sold} فروش · موجودی ${p.stock}`"
            />
          </VList>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
