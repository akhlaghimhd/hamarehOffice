<script setup lang="ts">
const statistics = [
  {
    title: 'فروش',
    stats: '۲۴۵ هزار',
    icon: 'ri-pie-chart-2-line',
    color: 'primary',
  },
  {
    title: 'مشتریان',
    stats: '۱۲.۵ هزار',
    icon: 'ri-group-line',
    color: 'success',
  },
  {
    title: 'محصول',
    stats: '۱.۵۴ هزار',
    icon: 'ri-macbook-line',
    color: 'warning',
  },
  {
    title: 'درآمد',
    stats: '۸۸ میلیون',
    icon: 'ri-money-dollar-circle-line',
    color: 'info',
  },
]

const moreList = [
  { title: 'اشتراک‌گذاری', value: 'Share' },
  { title: 'بروزرسانی', value: 'Refresh' },
  { title: 'ویرایش', value: 'Update' },
]
</script>

<template>
  <VCard title="تراکنش‌ها">
    <template #subtitle>
      <p class="text-body-1 mb-0">
        <span class="d-inline-block font-weight-medium text-high-emphasis">رشد کل ۴۸.۵٪</span>
        <span class="text-high-emphasis">😎</span>
        در این ماه
      </p>
    </template>

    <template #append>
      <MoreBtn :menu-list="moreList" />
    </template>

    <VCardText class="pt-10">
      <VRow>
        <VCol
          v-for="item in statistics"
          :key="item.title"
          cols="12"
          sm="6"
          md="3"
        >
          <div class="d-flex align-center gap-x-3">
            <VAvatar
              :color="item.color"
              rounded
              size="40"
              class="elevation-2"
            >
              <VIcon
                size="24"
                :icon="item.icon"
              />
            </VAvatar>

            <div class="d-flex flex-column">
              <div class="text-body-1">
                {{ item.title }}
              </div>
              <h5 class="text-h5">
                {{ item.stats }}
              </h5>
            </div>
          </div>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
