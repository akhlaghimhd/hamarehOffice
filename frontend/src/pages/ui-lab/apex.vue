<script setup lang="ts">
import VueApexCharts from 'vue3-apexcharts'

const lineSeries = [{ name: 'فروش', data: [28, 40, 36, 52, 38, 60, 55, 70] }]
const lineOptions = {
  chart: { toolbar: { show: false } },
  stroke: { curve: 'smooth', width: 3 },
  colors: ['#8C57FF'],
  xaxis: { categories: ['فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا'] },
  grid: { borderColor: '#eee' },
}

const barSeries = [{ name: 'فرصت', data: [12, 18, 9, 22, 15, 28] }]
const barOptions = {
  chart: { toolbar: { show: false } },
  plotOptions: { bar: { borderRadius: 6, columnWidth: '40%' } },
  colors: ['#16B1FF'],
  xaxis: { categories: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'] },
  grid: { borderColor: '#eee' },
}

const donutSeries = [44, 25, 18, 13]
const donutOptions = {
  labels: ['سرنخ', 'پیشنهاد', 'مذاکره', 'برد'],
  colors: ['#8C57FF', '#16B1FF', '#FFB400', '#56CA00'],
  legend: { position: 'bottom' },
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VRow>
      <VCol cols="12" md="8">
        <VCard title="نمودار خطی فروش">
          <VCardText>
            <VueApexCharts type="line" height="280" :options="lineOptions" :series="lineSeries" />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard title="توزیع قیف">
          <VCardText>
            <VueApexCharts type="donut" height="280" :options="donutOptions" :series="donutSeries" />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard title="فرصت‌های ماهانه">
          <VCardText>
            <VueApexCharts type="bar" height="280" :options="barOptions" :series="barSeries" />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
