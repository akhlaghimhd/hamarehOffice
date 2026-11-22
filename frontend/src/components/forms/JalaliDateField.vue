<script setup lang="ts">
import {
  formatJalaliFa,
  isFixedJalaliHoliday,
  isFridayIso,
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

function todayParts() {
  return isoToJalaliParts(todayIso()) || { jy: 1404, jm: 1, jd: 1 }
}

const view = ref(todayParts())

function syncViewFromModel() {
  const p = isoToJalaliParts(model.value || todayIso())
  if (p)
    view.value = { jy: p.jy, jm: p.jm, jd: p.jd }
}

watch(() => model.value, syncViewFromModel, { immediate: true })

watch(menu, (open) => {
  if (open)
    syncViewFromModel()
})

const monthNames = [
  'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
  'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
]

const weekDays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']

const monthItems = monthNames.map((title, i) => ({
  title,
  value: i + 1,
}))

const yearItems = computed(() => {
  const center = todayParts().jy
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
  const cells: Array<{
    day: number | null
    iso: string | null
    friday: boolean
    holiday: boolean
  }> = []

  for (let i = 0; i < offset; i++)
    cells.push({ day: null, iso: null, friday: false, holiday: false })

  for (let d = 1; d <= len; d++) {
    const [y, m, day] = jalaliToGregorian(jy, jm, d)
    const iso = y + '-' + String(m).padStart(2, '0') + '-' + String(day).padStart(2, '0')
    cells.push({
      day: d,
      iso,
      friday: isFridayIso(iso),
      holiday: isFixedJalaliHoliday(jy, jm, d),
    })
  }
  return cells
})

function prevMonth() {
  if (view.value.jm === 1)
    view.value = { jy: view.value.jy - 1, jm: 12, jd: 1 }
  else
    view.value = { jy: view.value.jy, jm: view.value.jm - 1, jd: 1 }
}

function nextMonth() {
  if (view.value.jm === 12)
    view.value = { jy: view.value.jy + 1, jm: 1, jd: 1 }
  else
    view.value = { jy: view.value.jy, jm: view.value.jm + 1, jd: 1 }
}

function onMonthChange(jm: number) {
  view.value = { jy: view.value.jy, jm, jd: 1 }
}

function onYearChange(jy: number) {
  view.value = { jy, jm: view.value.jm, jd: 1 }
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

function pickToday() {
  const iso = todayIso()
  model.value = iso
  syncViewFromModel()
  menu.value = false
}

const display = computed(() => formatJalaliFa(model.value))
const todayIsoStr = todayIso()
</script>

<template>
  <VMenu
    v-model="menu"
    :close-on-content-click="false"
    location="bottom start"
    max-width="280"
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

      <div class="jalali-picker__nav">
        <VBtn
          icon
          variant="text"
          size="x-small"
          @click="nextMonth"
        >
          <VIcon
            icon="ri-arrow-right-s-line"
            size="18"
          />
        </VBtn>
        <span class="text-caption text-medium-emphasis">
          {{ monthNames[view.jm - 1] }} {{ toFaDigits(view.jy) }}
        </span>
        <VBtn
          icon
          variant="text"
          size="x-small"
          @click="prevMonth"
        >
          <VIcon
            icon="ri-arrow-left-s-line"
            size="18"
          />
        </VBtn>
      </div>

      <div class="jalali-picker__week">
        <span
          v-for="w in weekDays"
          :key="w"
          class="jalali-picker__cell jalali-picker__cell--head"
          :class="{ 'jalali-picker__cell--friday-head': w === 'ج' }"
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
            'jalali-picker__cell--today': c.iso === todayIsoStr && c.iso !== model,
            'jalali-picker__cell--friday': c.friday && !c.holiday,
            'jalali-picker__cell--holiday': c.holiday,
          }"
          :disabled="!c.day"
          @click="pick(c.iso)"
        >
          {{ c.day ? toFaDigits(c.day) : '' }}
        </button>
      </div>

      <div class="jalali-picker__footer">
        <VBtn
          size="x-small"
          variant="text"
          color="secondary"
          @click="clear"
        >
          پاک کردن
        </VBtn>
        <VBtn
          size="x-small"
          color="primary"
          variant="tonal"
          @click="pickToday"
        >
          امروز
        </VBtn>
      </div>
    </VCard>
  </VMenu>
</template>

<style scoped>
.jalali-picker {
  width: 260px;
  max-width: 92vw;
  padding: 8px 8px 6px;
}

.jalali-picker__selectors {
  display: grid;
  grid-template-columns: 1.35fr 1fr;
  gap: 6px;
  margin-bottom: 4px;
}

.jalali-picker__month,
.jalali-picker__year {
  min-width: 0;
}

.jalali-picker__month :deep(.v-field),
.jalali-picker__year :deep(.v-field) {
  font-size: 0.8rem;
}

.jalali-picker__nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2px;
}

.jalali-picker__week,
.jalali-picker__grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
}

.jalali-picker__week {
  margin-bottom: 2px;
}

.jalali-picker__cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 0;
  background: transparent;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
  color: inherit;
  min-height: 30px;
  padding: 0;
}

.jalali-picker__cell--head {
  cursor: default;
  font-size: 0.7rem;
  opacity: 0.65;
  min-height: 22px;
}

.jalali-picker__cell--friday-head {
  color: rgb(var(--v-theme-error));
  opacity: 1;
  font-weight: 600;
}

.jalali-picker__cell--empty {
  cursor: default;
  pointer-events: none;
}

.jalali-picker__cell:not(.jalali-picker__cell--empty):not(.jalali-picker__cell--head):hover {
  background: rgba(var(--v-theme-primary), 0.08);
}

.jalali-picker__cell--friday {
  color: rgb(var(--v-theme-error));
}

.jalali-picker__cell--holiday {
  color: rgb(var(--v-theme-error));
  font-weight: 600;
  background: rgba(var(--v-theme-error), 0.08);
}

.jalali-picker__cell--today {
  box-shadow: inset 0 0 0 1.5px rgb(var(--v-theme-primary));
}

.jalali-picker__cell--active {
  background: rgb(var(--v-theme-primary)) !important;
  color: rgb(var(--v-theme-on-primary)) !important;
  font-weight: 600;
  box-shadow: none;
}

.jalali-picker__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 4px;
}
</style>
