<script setup lang="ts">
const search = ref('')
const items = [
  { code: 'OPP-۰۰۱', org: 'صنایع آفتاب', stage: 'سرنخ', status: 'فعال', amount: '۱۲ م' },
  { code: 'OPP-۰۰۲', org: 'پتروشیمی پارس', stage: 'پیشنهاد', status: 'فعال', amount: '۴۵ م' },
  { code: 'OPP-۰۰۳', org: 'معدن شرق', stage: 'مذاکره', status: 'متوقف', amount: '۸ م' },
  { code: 'OPP-۰۰۴', org: 'فناوری نوآور', stage: 'برنده', status: 'موفق', amount: '۲۲ م' },
]
const headers = [
  { title: 'کد', key: 'code' },
  { title: 'سازمان', key: 'org' },
  { title: 'مرحله', key: 'stage' },
  { title: 'وضعیت', key: 'status' },
  { title: 'مبلغ', key: 'amount' },
]
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="جدول داده">
      <VCardText>
        <VTextField
          v-model="search"
          label="جستجو"
          prepend-inner-icon="ri-search-line"
          class="mb-4"
          style="max-width: 280px"
        />
        <VDataTable
          :headers="headers"
          :items="items"
          :search="search"
          items-per-page="5"
          class="text-no-wrap"
        >
          <template #item.status="{ item }">
            <VChip
              size="small"
              variant="tonal"
              :color="item.status === 'موفق' ? 'success' : item.status === 'متوقف' ? 'warning' : 'primary'"
            >
              {{ item.status }}
            </VChip>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>
  </div>
</template>
