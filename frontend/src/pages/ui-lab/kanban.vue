<script setup lang="ts">
type Card = { id: number; title: string; org: string }
type Col = { key: string; title: string; color: string; items: Card[] }

const columns = ref<Col[]>([
  {
    key: 'lead',
    title: 'سرنخ',
    color: 'secondary',
    items: [
      { id: 1, title: 'OPP-۰۰۱', org: 'صنایع آفتاب' },
      { id: 2, title: 'OPP-۰۰۵', org: 'معدن شرق' },
    ],
  },
  {
    key: 'qualify',
    title: 'واجد شرایط',
    color: 'info',
    items: [{ id: 3, title: 'OPP-۰۰۲', org: 'پتروشیمی پارس' }],
  },
  {
    key: 'proposal',
    title: 'پیشنهاد',
    color: 'warning',
    items: [{ id: 4, title: 'OPP-۰۰۳', org: 'فناوری نوآور' }],
  },
  {
    key: 'won',
    title: 'برد',
    color: 'success',
    items: [{ id: 5, title: 'OPP-۰۰۴', org: 'خودروسازان' }],
  },
])
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <div class="d-flex align-center justify-space-between mb-4">
      <h5 class="text-h5 mb-0">کانبان قیف فروش (ساده)</h5>
      <VChip size="small" variant="tonal">بدون drag کتابخانه — الگوی ستون‌ها</VChip>
    </div>
    <div class="d-flex gap-4 overflow-x-auto pb-4">
      <VCard
        v-for="col in columns"
        :key="col.key"
        min-width="240"
        max-width="280"
        class="flex-shrink-0"
      >
        <VCardItem class="py-3">
          <VCardTitle class="text-body-1 d-flex align-center gap-2">
            <VChip :color="col.color" size="x-small" variant="tonal">{{ col.items.length }}</VChip>
            {{ col.title }}
          </VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText class="d-flex flex-column gap-3">
          <VCard
            v-for="item in col.items"
            :key="item.id"
            variant="outlined"
          >
            <VCardText class="py-3">
              <div class="font-weight-medium">{{ item.title }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.org }}</div>
            </VCardText>
          </VCard>
          <VBtn variant="text" size="small" block prepend-icon="ri-add-line">افزودن</VBtn>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>
