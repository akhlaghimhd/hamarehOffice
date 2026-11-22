<script setup lang="ts">
/**
 * UI Lab — موقت
 */

type LabItem = {
  title: string
  to: string
  status: 'ready' | 'partial'
}

type LabGroup = {
  title: string
  items: LabItem[]
}

const groups: LabGroup[] = [
  {
    title: 'داشبوردها',
    items: [
      { title: 'Analytics', to: '/ui-lab/dashboards-analytics', status: 'ready' },
      { title: 'Ecommerce', to: '/ui-lab/dashboards-ecommerce', status: 'ready' },
      { title: 'Academy', to: '/ui-lab/dashboards-academy', status: 'ready' },
      { title: 'Logistics', to: '/ui-lab/dashboards-logistics', status: 'ready' },
      { title: 'داشبورد پروژه', to: '/dashboard', status: 'partial' },
    ],
  },
  {
    title: 'عناصر پایه',
    items: [
      { title: 'تایپوگرافی', to: '/typography', status: 'ready' },
      { title: 'آیکن‌ها', to: '/icons', status: 'ready' },
      { title: 'دکمه‌ها', to: '/ui-lab/buttons', status: 'ready' },
      { title: 'هشدار (Alert)', to: '/ui-lab/alerts', status: 'ready' },
      { title: 'آواتار', to: '/ui-lab/avatars', status: 'ready' },
      { title: 'بج', to: '/ui-lab/badges', status: 'ready' },
      { title: 'چیپ', to: '/ui-lab/chips', status: 'ready' },
      { title: 'دیالوگ', to: '/ui-lab/dialogs', status: 'ready' },
      { title: 'Expansion Panel', to: '/ui-lab/expansion', status: 'ready' },
      { title: 'لیست', to: '/ui-lab/lists', status: 'ready' },
      { title: 'منو', to: '/ui-lab/menus', status: 'ready' },
      { title: 'صفحه‌بندی', to: '/ui-lab/pagination', status: 'ready' },
      { title: 'پیشرفت دایره‌ای', to: '/ui-lab/progress-circular', status: 'ready' },
      { title: 'پیشرفت خطی', to: '/ui-lab/progress-linear', status: 'ready' },
      { title: 'اسنک‌بار', to: '/ui-lab/snackbars', status: 'ready' },
      { title: 'تب‌ها', to: '/ui-lab/tabs', status: 'ready' },
      { title: 'تایم‌لاین', to: '/ui-lab/timeline', status: 'ready' },
      { title: 'تولتیپ', to: '/ui-lab/tooltips', status: 'ready' },
    ],
  },
  {
    title: 'کارت‌ها',
    items: [
      { title: 'کارت ساده', to: '/cards', status: 'ready' },
      { title: 'کارت پیشرفته', to: '/ui-lab/card-advance', status: 'ready' },
      { title: 'کارت ویجت', to: '/ui-lab/card-widgets', status: 'ready' },
      { title: 'کارت آمار', to: '/ui-lab/card-statistics', status: 'ready' },
      { title: 'کارت گیمیفیکیشن', to: '/ui-lab/card-gamification', status: 'ready' },
      { title: 'اکشن کارت', to: '/ui-lab/card-actions', status: 'ready' },
    ],
  },
  {
    title: 'فرم‌ها',
    items: [
      { title: 'Text Field', to: '/ui-lab/textfields', status: 'ready' },
      { title: 'Textarea', to: '/ui-lab/textareas', status: 'ready' },
      { title: 'Select', to: '/ui-lab/selects', status: 'ready' },
      { title: 'Checkbox / Radio / Switch', to: '/ui-lab/toggles', status: 'ready' },
      { title: 'چیدمان فرم', to: '/form-layouts', status: 'ready' },
      { title: 'اعتبارسنجی فرم', to: '/ui-lab/form-validation', status: 'ready' },
      { title: 'فرم ویزارد', to: '/ui-lab/form-wizard', status: 'ready' },
      { title: 'Autocomplete / Combobox', to: '/ui-lab/autocomplete', status: 'ready' },
      { title: 'File / Slider / Rating', to: '/ui-lab/inputs-extra', status: 'ready' },
    ],
  },
  {
    title: 'جداول و نمودار',
    items: [
      { title: 'جدول ساده', to: '/tables', status: 'ready' },
      { title: 'Data Table', to: '/ui-lab/data-table', status: 'ready' },
      { title: 'Apex Chart', to: '/ui-lab/apex', status: 'ready' },
      { title: 'Kanban', to: '/ui-lab/kanban', status: 'ready' },
      { title: 'Account Settings', to: '/account-settings', status: 'ready' },
    ],
  },
]

const statusColor: Record<string, string> = {
  ready: 'success',
  partial: 'warning',
}
const statusLabel: Record<string, string> = {
  ready: 'آماده',
  partial: 'ناقص',
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h4 class="text-h4 mb-1">
        الگوهای UI (موقت)
      </h4>
      <p class="text-body-2 text-medium-emphasis mb-0">
        مرجع ظاهر Materio برای یکدست‌سازی. داشبوردها با تصویر ۳بعدی و انیمیشن ورودی.
      </p>
    </div>

    <VRow>
      <VCol
        v-for="group in groups"
        :key="group.title"
        cols="12"
        md="6"
        lg="4"
      >
        <VCard>
          <VCardItem>
            <VCardTitle>{{ group.title }}</VCardTitle>
          </VCardItem>
          <VList density="compact">
            <VListItem
              v-for="item in group.items"
              :key="item.title"
              :to="item.to"
              rounded="lg"
              class="mx-2 mb-1"
            >
              <template #prepend>
                <VIcon icon="ri-layout-2-line" size="18" class="me-1" />
              </template>
              <VListItemTitle class="text-body-2">
                {{ item.title }}
              </VListItemTitle>
              <template #append>
                <VChip :color="statusColor[item.status]" size="x-small" variant="tonal">
                  {{ statusLabel[item.status] }}
                </VChip>
              </template>
            </VListItem>
          </VList>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
