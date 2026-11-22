/** نمایش اعداد فارسی */
export function toFaDigits(value: string | number | null | undefined): string {
  if (value === null || value === undefined)
    return ''
  return String(value).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[Number(d)])
}

/** تبدیل ارقام فارسی/عربی به لاتین */
export function toEnDigits(value: string): string {
  return value
    .replace(/[۰-۹]/g, d => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)))
    .replace(/[٠-٩]/g, d => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)))
}

export function onlyDigits(value: string): string {
  return toEnDigits(value).replace(/\D/g, '')
}

export function formatRialFa(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '')
    return ''
  const raw = onlyDigits(String(value))
  if (!raw)
    return ''
  const withSep = raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return toFaDigits(withSep)
}

export function parseRialInput(value: string): string {
  return onlyDigits(value)
}

function div(a: number, b: number) {
  return Math.trunc(a / b)
}

/** سال کبیسه شمسی (الگوریتم رایج %33) */
export function isJalaliLeap(jy: number): boolean {
  const r = jy % 33
  return [1, 5, 9, 13, 17, 22, 26, 30].includes(r)
}

export function jalaliMonthLength(jy: number, jm: number): number {
  if (jm <= 6)
    return 31
  if (jm <= 11)
    return 30
  return isJalaliLeap(jy) ? 30 : 29
}

/** Gregorian Y-M-D → [jy, jm, jd] — الگوریتم استاندارد تأییدشده */
export function gregorianToJalali(gy: number, gm: number, gd: number): [number, number, number] {
  const gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
  let jy: number
  let gy2 = gy

  if (gy2 > 1600) {
    jy = 979
    gy2 -= 1600
  }
  else {
    jy = 0
    gy2 -= 621
  }

  const gy3 = gm > 2 ? gy2 + 1 : gy2
  let days = 365 * gy2
    + div(gy3 + 3, 4)
    - div(gy3 + 99, 100)
    + div(gy3 + 399, 400)
    - 80
    + gd
    + gdm[gm - 1]

  jy += 33 * div(days, 12053)
  days %= 12053
  jy += 4 * div(days, 1461)
  days %= 1461

  if (days > 365) {
    jy += div(days - 1, 365)
    days = (days - 1) % 365
  }

  let jm: number
  let jd: number
  if (days < 186) {
    jm = 1 + div(days, 31)
    jd = 1 + (days % 31)
  }
  else {
    jm = 7 + div(days - 186, 30)
    jd = 1 + ((days - 186) % 30)
  }

  return [jy, jm, jd]
}

/** Jalali → Gregorian */
export function jalaliToGregorian(jy: number, jm: number, jd: number): [number, number, number] {
  let gy: number
  let jy2 = jy

  if (jy2 > 979) {
    gy = 1600
    jy2 -= 979
  }
  else {
    gy = 621
  }

  let days = 365 * jy2
    + div(jy2, 33) * 8
    + div((jy2 % 33) + 3, 4)
    + 78
    + jd
    + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186)

  gy += 400 * div(days, 146097)
  days %= 146097

  if (days > 36524) {
    days--
    gy += 100 * div(days, 36524)
    days %= 36524
    if (days >= 365)
      days++
  }

  gy += 4 * div(days, 1461)
  days %= 1461

  if (days > 365) {
    gy += div(days - 1, 365)
    days = (days - 1) % 365
  }

  let gd = days + 1
  const leap = (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0
  const salA = [0, 31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
  let gm = 0
  for (let i = 1; i <= 12; i++) {
    if (gd <= salA[i]) {
      gm = i
      break
    }
    gd -= salA[i]
  }

  return [gy, gm, gd]
}

export function formatJalaliFa(iso: string | null | undefined): string {
  if (!iso)
    return '—'
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso)
  if (!m)
    return toFaDigits(iso)
  const [jy, jm, jd] = gregorianToJalali(+m[1], +m[2], +m[3])
  const s = `${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`
  return toFaDigits(s)
}

export function jalaliInputToIso(input: string | null | undefined): string | null {
  if (!input || !String(input).trim())
    return null
  const cleaned = toEnDigits(String(input).trim()).replace(/[.-]/g, '/')
  const parts = cleaned.split('/')
  if (parts.length !== 3)
    return null
  const jy = Number(parts[0])
  const jm = Number(parts[1])
  const jd = Number(parts[2])
  if (!jy || !jm || !jd)
    return null
  const [gy, gm, gd] = jalaliToGregorian(jy, jm, jd)
  return `${gy}-${String(gm).padStart(2, '0')}-${String(gd).padStart(2, '0')}`
}

export function isoToJalaliParts(iso: string | null | undefined): { jy: number; jm: number; jd: number } | null {
  if (!iso)
    return null
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso)
  if (!m)
    return null
  const [jy, jm, jd] = gregorianToJalali(+m[1], +m[2], +m[3])
  return { jy, jm, jd }
}

export function todayIso(): string {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** تعطیلات ثابت شمسی (ملی) — بدون اعیاد قمری متغیر */
const FIXED_HOLIDAYS: Array<[number, number]> = [
  [1, 1], [1, 2], [1, 3], [1, 4], // نوروز
  [1, 12], // روز جمهوری اسلامی
  [1, 13], // سیزده‌بدر
  [3, 14], // رحلت امام خمینی
  [3, 15], // قیام ۱۵ خرداد
  [11, 22], // پیروزی انقلاب
  [12, 29], // ملی شدن نفت
]

export function isFixedJalaliHoliday(jy: number, jm: number, jd: number): boolean {
  void jy
  return FIXED_HOLIDAYS.some(([m, d]) => m === jm && d === jd)
}

/** آیا این ISO در تقویم ایران جمعه است؟ */
export function isFridayIso(iso: string): boolean {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso)
  if (!m)
    return false
  const dt = new Date(+m[1], +m[2] - 1, +m[3])
  return dt.getDay() === 5 // جمعه
}
