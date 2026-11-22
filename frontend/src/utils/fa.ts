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

/** فقط ارقام لاتین */
export function onlyDigits(value: string): string {
  return toEnDigits(value).replace(/\D/g, '')
}

/** فرمت مبلغ ریال با جداکننده هزارگان + ارقام فارسی */
export function formatRialFa(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '')
    return ''
  const raw = onlyDigits(String(value))
  if (!raw)
    return ''
  const withSep = raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return toFaDigits(withSep)
}

/** ورودی مبلغ نمایشی → عدد صحیح ریال (string بدون جداکننده) */
export function parseRialInput(value: string): string {
  return onlyDigits(value)
}

function div(a: number, b: number) {
  return Math.trunc(a / b)
}

export function gregorianToJalali(gy: number, gm: number, gd: number): [number, number, number] {
  const gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
  let gy2 = gy - 1600
  let gm2 = gm - 1
  let gd2 = gd - 1

  let gDayNo = 365 * gy2 + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) - 80 + gd2 + gdm[gm2]
  if (gm2 > 1 && ((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0))
    gDayNo += 1

  let jDayNo = gDayNo - 79
  const jNp = div(jDayNo, 12053)
  jDayNo %= 12053
  let jy = 979 + 33 * jNp + 4 * div(jDayNo, 1461)
  jDayNo %= 1461

  if (jDayNo >= 366) {
    jy += div(jDayNo - 1, 365)
    jDayNo = (jDayNo - 1) % 365
  }

  let jm: number
  let jd: number
  if (jDayNo < 186) {
    jm = 1 + div(jDayNo, 31)
    jd = 1 + (jDayNo % 31)
  }
  else {
    jm = 7 + div(jDayNo - 186, 30)
    jd = 1 + ((jDayNo - 186) % 30)
  }

  return [jy, jm, jd]
}

export function jalaliToGregorian(jy: number, jm: number, jd: number): [number, number, number] {
  jy -= 979
  jm -= 1
  jd -= 1

  let jDayNo = 365 * jy + div(jy, 33) * 8 + div((jy % 33) + 3, 4) + 78 + jd + (jm < 7 ? jm * 31 : (jm - 7) * 30 + 186)
  let gDayNo = jDayNo + 79

  let gy = 1600 + 400 * div(gDayNo, 146097)
  gDayNo %= 146097

  let leap = true
  if (gDayNo >= 36525) {
    gDayNo--
    gy += 100 * div(gDayNo, 36524)
    gDayNo %= 36524
    if (gDayNo >= 365)
      gDayNo++
    else leap = false
  }

  gy += 4 * div(gDayNo, 1461)
  gDayNo %= 1461

  if (gDayNo >= 366) {
    leap = false
    gDayNo--
    gy += div(gDayNo, 365)
    gDayNo %= 365
  }

  const salA = [0, 31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
  let gm = 0
  while (gm < 13 && gDayNo >= salA[gm]) {
    gDayNo -= salA[gm]
    gm++
  }

  return [gy, gm, gDayNo + 1]
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

export function jalaliMonthLength(jy: number, jm: number): number {
  if (jm <= 6)
    return 31
  if (jm <= 11)
    return 30
  // اسفند: سال کبیسه جلالی تقریبی
  const breaks = [1, 5, 9, 13, 17, 22, 26, 30]
  const cy = jy - 979
  const leap = breaks.includes(cy % 33)
  return leap ? 30 : 29
}

export function todayIso(): string {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
