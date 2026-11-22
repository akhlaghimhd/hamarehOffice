<script setup lang="ts">
import {
  formatJalaliFa,
  gregorianToJalali,
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
    if (p) {
      view.value = { jy: p.jy, jm: p.jm }
    }
  },
  { immediate: true },
)

const monthNames = [
  'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
  'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
]

const days = computed(() => {
  const { jy, jm } = view.value
  const len = jalaliMonthLength(jy, jm)
  const [gy, gm, gd] = jalaliToGregorian(jy, jm, 1)
  const first = new Date(gy, gm - 1, gd)
  // شنبه = 0 در تقویم ایران: JS: 0=یکشنبه ... 6=شنبه
  const jsDay = first.getDay()
  const offset = (jsDay + 1) % 7
  const cells: Array<{ day: number | null; iso: string | null }> = []
  for (let i = 0; i < offset; i++)
    cells.push({ day: null, iso: null })
  for (let d = 1; d <= len; d++) {
    const [y, m, day] = jalaliToGregorian(jy, jm, d)
    const iso = `${y}-${String(m).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    cells.push({ day: d, iso })
  }
  return cells
})

function prevMonth() {
  if (view.value.jm === 1) {
    view.value = { jy: view.value.jy - 1, jm: 12 }
  }
  else {
    view.value = { jy: view.value.jy, jm: view.value.jm - 1 }
  }
}

function nextMonth() {
  if (view.value.jm === 12) {
    view.value = { jy: view.value.jy + 1, jm: 1 }
  }
  else {
    view.value = { jy: view.value.jy, jm: view.value.jm + 1 }
  }
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
    location="bottom"
  >
    <template #activator="{ props: act }">
      <VTextField
        :model-value="display === '—' ? '' : display"
        :label="label || 'تاریخ'"
        readonly
        :rules="required ? [(v: string) => !!v || 'تاریخ الزامی است'] : []"
        append-inner-icon="ri-calendar-line"
        v-bind="act"
        hint="انتخاب از تقویم شمسی"
        persistent-hint
      />
    </template>

    <VCard min-width="300">
      <VCardTitle class="d-flex align-center justify-space-between py-2">
        <VBtn
          icon
          variant="text"
          size="small"
          @click="prevMonth"
        >
          <VIcon icon="ri-arrow-right-s-line" />
        </VBtn>
        <span class="text-body-1">
          {{ monthNames[view.jm - 1] }} {{ toFaDigits(view.jy) }}
        </span>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="nextMonth"
        >
          <VIcon icon="ri-arrow-left-s-line" />
        </VBtn>
      </VCardTitle>

      <VCardText class="pt-0">
        <div
          class="d-grid text-center text-caption text-medium-emphasis mb-1"
          style="grid-template-columns: repeat(7, 1fr);"
        >
          <span
            v-for="w in ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']"
            :key="w"
          >{{ w }}</span>
        </div>
        <div
          class="d-grid"
          style="grid-template-columns: repeat(7, 1fr); gap: 2px;"
        >
          <VBtn
            v-for="(c, i) in days"
            :key="i"
            size="small"
            variant="text"
            :disabled="!c.day"
            :color="c.iso && c.iso === model ? 'primary' : undefined"
            :class="{ 'font-weight-bold': c.iso === model }"
            @click="pick(c.iso)"
          >
            {{ c.day ? toFaDigits(c.day) : '' }}
          </VBtn>
        </div>
      </VCardText>

      <VCardActions>
        <VBtn
          size="small"
          variant="text"
          @click="clear"
        >
          پاک کردن
        </VBtn>
        <VSpacer />
        <VBtn
          size="small"
          color="primary"
          variant="tonal"
          @click="pick(todayIso())"
        >
          امروز
        </VBtn>
      </VCardActions>
    </VCard>
  </VMenu>
</template>
