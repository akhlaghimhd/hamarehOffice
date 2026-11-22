<script setup lang="ts">
import {
  formatJalaliFa,
  isoToJalaliParts,
  jalaliMonthLength,
  jalaliToGregorian,
  toFaDigits,
  todayIso,
} from '@/utils/fa'

/** v-model = ISO Y-m-d یا null */
const model = defineModel<string | null>({ default: null })

defineProps<{
  label?: string
  required?: boolean
}>()

const menu = ref(false)
const view = ref({ jy: 1404, jm: 1 })

watch(
  () => model.value,
  (iso) => {
    const p = isoToJalaliParts(iso || todayIso())
    if (p)
      view.value = { jy: p.jy, jm: p.jm }
  },
  { immediate: true },
)

const monthNames = [
  'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
  'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
]

const weekDays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']

const monthItems = monthNames.map((title, i) => ({
  title,
  value: i + 1,
}))

/** بازه سال‌ها: حدود ۲۰ سال قبل تا ۱۰ سال بعد نسبت به امروز شمسی */
const yearItems = computed(() => {
  const today = isoToJalaliParts(todayIso())
  const center = today?.jy ?? 1404
  const list: { title: string; value: number }[] = []
  for (let y = center - 20; y <= center + 10; y++)
    list.push({ title: toFaDigits(y), value: y })
  return list
})

const days = computed(() => {
  const { jy, jm } = view.value
  const len = jalaliMonthLength(jy, jm)
  const [gy, gm, gd] = jalaliToGregorian(jy, jm, 1)
  const first = new Date(gy, gm - 1, gd)
  const offset = (first.getDay() + 1) % 7
  const cells: Array<{ day: number | null; iso: string | null }> = []
  for (let i = 0; i < offset; i++)
    cells.push({ day: null, iso: null })
  for (let d = 1; d <= len; d++) {
    const [y, m, day] = jalaliToGregorian(jy, jm, d)
    const iso = y + '-' + String(m).padStart(2, '0') + '-' + String(day).padStart(2, '0')
    cells.push({ day: d, iso })
  }
  return cells
})

function prevMonth() {
  if (view.value.jm === 1)
    view.value = { jy: view.value.jy - 1, jm: 12 }
  else
    view.value = { jy: view.value.jy, jm: view.value.jm - 1 }
}

function nextMonth() {
  if (view.value.jm === 12)
    view.value = { jy: view.value.jy + 1, jm: 1 }
  else
    view.value = { jy: view.value.jy, jm: view.value.jm + 1 }
}

function onMonthChange(jm: number) {
  view.value = { jy: view.value.jy, jm }
}

function onYearChange(jy: number) {
  view.value = { jy, jm: view.value.jm }
}

function pick(iso: string | null) {
  if (!iso)
    return
  model.value = iso
  menu.value = false
}

function clear() {
  model.value = null
  menu.value = false
}

const display = computed(() => formatJalaliFa(model.value))
</script>

<template>
  <VMenu
    v-model="menu"
    :close-on-content-click="false"
    location="bottom start"
    max-width="320"
  >
    <template #activator="{ props: act }">
      <VTextField
        :model-value="display === '—' ? '' : display"
        :label="label || 'تاریخ'"
        readonly
        :rules="required ? [(v: string) => !!v || 'تاریخ الزامی است'] : []"
        prepend-inner-icon="ri-calendar-line"
        v-bind="act"
        hint="انتخاب از تقویم شمسی"
        persistent-hint
      />
    </template>

    <VCard class="jalali-picker">
      <div class="jalali-picker__header">
        <VBtn
          icon
          variant="text"
          size="small"
          @click="nextMonth"
        >
          <VIcon icon="ri-arrow-right-s-line" />
        </VBtn>

        <div class="jalali-picker__selectors">
          <VSelect
            :model-value="view.jm"
            :items="monthItems"
            density="compact"
            hide-details
            variant="outlined"
            class="jalali-picker__month"
            @update:model-value="onMonthChange"
          />
          <VSelect
            :model-value="view.jy"
            :items="yearItems"
            density="compact"
            hide-details
            variant="outlined"
            class="jalali-picker__year"
            @update:model-value="onYearChange"
          />
        </div>

        <VBtn
          icon
          variant="text"
          size="small"
          @click="prevMonth"
        >
          <VIcon icon="ri-arrow-left-s-line" />
        </VBtn>
      </div>

      <div class="jalali-picker__week">
        <span
          v-for="w in weekDays"
          :key="w"
          class="jalali-picker__cell jalali-picker__cell--head"
        >{{ w }}</span>
      </div>

      <div class="jalali-picker__grid">
        <button
          v-for="(c, i) in days"
          :key="i"
          type="button"
          class="jalali-picker__cell"
          :class="{
            'jalali-picker__cell--empty': !c.day,
            'jalali-picker__cell--active': c.iso && c.iso === model,
          }"
          :disabled="!c.day"
          @click="pick(c.iso)"
        >
          {{ c.day ? toFaDigits(c.day) : '' }}
        </button>
      </div>

      <div class="jalali-picker__footer">
        <VBtn
          size="small"
          variant="text"
          color="secondary"
          @click="clear"
        >
          پاک کردن
        </VBtn>
        <VBtn
          size="small"
          color="primary"
          variant="tonal"
          @click="pick(todayIso())"
        >
          امروز
        </VBtn>
      </div>
    </VCard>
  </VMenu>
</template>

<style scoped>
.jalali-picker {
  width: 300px;
  max-width: 92vw;
  padding: 8px 10px 6px;
}

.jalali-picker__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 4px;
  margin-bottom: 8px;
}

.jalali-picker__selectors {
  display: flex;
  flex: 1;
  gap: 6px;
  min-width: 0;
}

.jalali-picker__month {
  flex: 1.4;
  min-width: 0;
}

.jalali-picker__year {
  flex: 1;
  min-width: 0;
}

.jalali-picker__week,
.jalali-picker__grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}

.jalali-picker__week {
  margin-bottom: 4px;
}

.jalali-picker__cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 0;
  background: transparent;
  border-radius: 8px;
  font-size: 0.875rem;
  cursor: pointer;
  color: inherit;
  min-height: 34px;
  padding: 0;
}

.jalali-picker__cell--head {
  cursor: default;
  font-size: 0.75rem;
  opacity: 0.65;
  min-height: 26px;
}

.jalali-picker__cell--empty {
  cursor: default;
  pointer-events: none;
}

.jalali-picker__cell:not(.jalali-picker__cell--empty):not(.jalali-picker__cell--head):hover {
  background: rgba(var(--v-theme-primary), 0.08);
}

.jalali-picker__cell--active {
  background: rgb(var(--v-theme-primary)) !important;
  color: rgb(var(--v-theme-on-primary)) !important;
  font-weight: 600;
}

.jalali-picker__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 6px;
  padding-top: 4px;
}
</style>
