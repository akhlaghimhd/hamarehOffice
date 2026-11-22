import { onlyDigits } from '@/utils/fa'

export const rules = {
  required: (label = 'این فیلد') => (v: unknown) => {
    if (v === null || v === undefined || v === '')
      return `${label} الزامی است`
    if (Array.isArray(v) && v.length === 0)
      return `${label} الزامی است`
    return true
  },

  maxLen: (max: number, label = 'مقدار') => (v: unknown) => {
    if (v === null || v === undefined || v === '')
      return true
    return String(v).length <= max || `${label} نباید بیشتر از ${max} نویسه باشد`
  },

  /** موبایل/تلفن ایران: دقیقاً ۱۱ رقم */
  phone11: (v: unknown) => {
    if (v === null || v === undefined || v === '')
      return true
    const d = onlyDigits(String(v))
    if (d.length === 0)
      return true
    if (d.length !== 11)
      return 'شماره باید دقیقاً ۱۱ رقم باشد'
    if (!/^0\d{10}$/.test(d))
      return 'شماره باید با ۰ شروع شود و ۱۱ رقم باشد'
    return true
  },

  mobile09: (v: unknown) => {
    if (v === null || v === undefined || v === '')
      return true
    const d = onlyDigits(String(v))
    if (d.length === 0)
      return true
    if (d.length !== 11)
      return 'موبایل باید دقیقاً ۱۱ رقم باشد'
    if (!/^09\d{9}$/.test(d))
      return 'موبایل باید با ۰۹ شروع شود'
    return true
  },

  rial: (v: unknown) => {
    if (v === null || v === undefined || v === '')
      return true
    const d = onlyDigits(String(v))
    return d.length > 0 || 'مبلغ نامعتبر است'
  },
}
