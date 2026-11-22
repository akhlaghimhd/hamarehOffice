<script setup lang="ts">
/**
 * UI Lab — موقت
 * الگوی مرجع ظاهر Materio برای آموزش و یکدست‌سازی.
 * پس از تثبیت پروژه قابل حذف کامل است.
 */

type LabItem = {
  title: string
  to: string
  status: 'ready' | 'partial' | 'todo'
  demo?: string
}

type LabGroup = {
  title: string
  items: LabItem[]
}

const groups: LabGroup[] = [
  {
    title: 'عناصر پایه',
    items: [
      { title: 'تایپوگرافی', to: '/typography', status: 'ready', demo: 'pages/typography' },
      { title: 'آیکن‌ها', to: '/icons', status: 'ready', demo: 'pages/icons' },
      { title: 'دکمه‌ها', to: '/ui-lab/buttons', status: 'ready', demo: 'components/button' },
      { title: 'هشدار (Alert)', to: '/ui-lab/alerts', status: 'ready', demo: 'components/alert' },
      { title: 'آواتار', to: '/ui-lab/avatars', status: 'ready', demo: 'components/avatar' },
      { title: 'بج', to: '/ui-lab/badges', status: 'ready', demo: 'components/badge' },
      { title: 'چیپ', to: '/ui-lab/chips', status: 'ready', demo: 'components/chip' },
      { title: 'دیالوگ', to: '/ui-lab/dialogs', status: 'ready', demo: 'components/dialog' },
      { title: 'Expansion Panel', to: '/ui-lab/expansion', status: 'ready', demo: 'components/expansion-panel' },
      { title: 'لیست', to: '/ui-lab/lists', status: 'ready', demo: 'components/list' },
      { title: 'منو', to: '/ui-lab/menus', status: 'ready', demo: 'components/menu' },
      { title: 'صفحه‌بندی', to: '/ui-lab/pagination', status: 'ready', demo: 'components/pagination' },
      { title: 'پیشرفت دایره‌ای', to: '/ui-lab/progress-circular', status: 'ready', demo: 'components/progress-circular' },
      { title: 'پیشرفت خطی', to: '/ui-lab/progress-linear', status: 'ready', demo: 'components/progress-linear' },
      { title: 'اسنک‌بار', to: '/ui-lab/snackbars', status: 'ready', demo: 'components/snackbar' },
      { title: 'تب‌ها', to: '/ui-lab/tabs', status: 'ready', demo: 'components/tabs' },
      { title: 'تایم‌لاین', to: '/ui-lab/timeline', status: 'partial', demo: 'components/timeline' },
      { title: 'تولتیپ', to: '/ui-lab/tooltips', status: 'ready', demo: 'components/tooltip' },
    ],
  },
  {
    title: 'کارت‌ها',
    items: [
      { title: 'کارت ساده', to: '/cards', status: 'ready', demo: 'pages/cards/card-basic' },
      { title: 'کارت پیشرفته', to: '/ui-lab/card-advance', status: 'todo', demo: 'pages/cards/card-advance' },
      { title: 'کارت ویجت', to: '/ui-lab/card-widgets', status: 'todo', demo: 'pages/cards/card-widgets' },
      { title: 'کارت آمار', to: '/ui-lab/card-statistics', status: 'todo', demo: 'pages/cards/card-statistics' },
      { title: 'کارت گیمیفیکیشن', to: '/ui-lab/card-gamification', status: 'todo', demo: 'pages/cards/card-gamification' },
      { title: 'اکشن کارت', to: '/ui-lab/card-actions', status: 'todo', demo: 'pages/cards/card-actions' },
    ],
  },
  {
    title: 'فرم‌ها',
    items: [
      { title: 'Text Field', to: '/ui-lab/textfields', status: 'ready', demo: 'forms/textfield' },
      { title: 'Textarea', to: '/ui-lab/textareas', status: 'ready', demo: 'forms/textarea' },
      { title: 'Select', to: '/ui-lab/selects', status: 'ready', demo: 'forms/select' },
      { title: 'Checkbox / Radio / Switch', to: '/ui-lab/toggles', status: 'ready', demo: 'forms/checkbox' },
      { title: 'چیدمان فرم', to: '/form-layouts', status: 'ready', demo: 'forms/form-layouts' },
      { title: 'اعتبارسنجی فرم', to: '/ui-lab/form-validation', status: 'partial', demo: 'forms/form-validation' },
      { title: 'فرم ویزارد', to: '/ui-lab/form-wizard', status: 'todo', demo: 'forms/form-wizard-numbered' },
      { title: 'Autocomplete / Combobox', to: '/ui-lab/autocomplete', status: 'todo', demo: 'forms/autocomplete' },
      { title: 'File / Slider / Rating', to: '/ui-lab/inputs-extra', status: 'todo', demo: 'forms/file-input' },
    ],
  },
  {
    title: 'جداول و نمودار',
    items: [
      { title: 'جدول ساده', to: '/tables', status: 'ready', demo: 'tables/simple-table' },
      { title: 'Data Table', to: '/ui-lab/data-table', status: 'todo', demo: 'tables/data-table' },
      { title: 'Apex Chart', to: '/ui-lab/apex', status: 'todo', demo: 'charts/apex-chart' },
    ],
  },
  {
    title: 'صفحات نمونه',
    items: [
      { title: 'داشبورد CRM', to: '/dashboard', status: 'partial', demo: 'dashboards/crm' },
      { title: 'Account Settings', to: '/account-settings', status: 'ready', demo: 'pages/account-settings' },
      { title: 'Kanban', to: '/ui-lab/kanban', status: 'todo', demo: 'apps/kanban' },
    ],
  },
]

const statusColor: Record<string, string> = {
  ready: 'success',
  partial: 'warning',
  todo: 'secondary',
}
const statusLabel: Record<string, string> = {
  ready: 'آماده',
  partial: 'ناقص',
  todo: 'در صف',
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h4 class="text-h4 mb-1">
        الگوهای UI (موقت)
      </h4>
      <p class="text-body-2 text-medium-emphasis mb-0">
        مرجع ظاهر Materio برای یکدست‌سازی فرم‌ها و صفحات پروژه. پس از تثبیت توسعه حذف می‌شود.
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
              :to="item.status === 'todo' ? undefined : item.to"
              :disabled="item.status === 'todo'"
              rounded="lg"
              class="mx-2 mb-1"
            >
              <template #prepend>
                <VIcon
                  icon="ri-layout-2-line"
                  size="18"
                  class="me-1"
                />
              </template>
              <VListItemTitle class="text-body-2">
                {{ item.title }}
              </VListItemTitle>
              <template #append>
                <VChip
                  :color="statusColor[item.status]"
                  size="x-small"
                  variant="tonal"
                >
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
