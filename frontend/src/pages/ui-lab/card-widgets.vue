<script setup lang="ts">
const series = [{ data: [28, 40, 36, 52, 38, 60, 55] }]
const chartOptions = {
  chart: { type: 'area', toolbar: { show: false }, sparkline: { enabled: true } },
  stroke: { curve: 'smooth', width: 2 },
  colors: ['#8C57FF'],
  fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.05 } },
  tooltip: { enabled: false },
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <p class="text-h5 mb-4">کارت ویجت</p>
    <VRow>
      <VCol cols="12" md="4">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between mb-2">
              <div>
                <div class="text-body-2 text-medium-emphasis">درآمد هفتگی</div>
                <div class="text-h5">۲۴٫۸ م</div>
              </div>
              <VAvatar color="primary" variant="tonal" rounded>
                <VIcon icon="ri-line-chart-line" />
              </VAvatar>
            </div>
            <VueApexCharts type="area" height="80" :options="chartOptions" :series="series" />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard>
          <VCardText>
            <div class="text-body-2 text-medium-emphasis mb-1">وظایف امروز</div>
            <div class="text-h5 mb-4">۷ از ۱۲</div>
            <VProgressLinear model-value="58" color="success" height="8" rounded class="mb-2" />
            <div class="text-caption text-medium-emphasis">۵ مورد باقی‌مانده</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard>
          <VCardText>
            <div class="d-flex align-center gap-3 mb-3">
              <VAvatar color="info" variant="tonal"><VIcon icon="ri-team-line" /></VAvatar>
              <div>
                <div class="text-body-1 font-weight-medium">تیم فروش</div>
                <div class="text-caption text-medium-emphasis">۴ نفر آنلاین</div>
              </div>
            </div>
            <div class="d-flex">
              <VAvatar size="32" color="primary" class="me-n2">م</VAvatar>
              <VAvatar size="32" color="success" class="me-n2">س</VAvatar>
              <VAvatar size="32" color="warning" class="me-n2">ر</VAvatar>
              <VAvatar size="32" color="info">+۲</VAvatar>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
