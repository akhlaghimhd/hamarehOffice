/** رنگ و آیکن مراحل قیف و انواع فعالیت */

export type VisualTone = {
  color: string
  icon: string
  label?: string
}

/** مراحل قیف — بر اساس code سیید FoundationSeeder */
export const STAGE_VISUAL: Record<string, VisualTone> = {
  initial_lead: { color: 'info', icon: 'ri-lightbulb-line', label: 'سرنخ اولیه' },
  quick_qualify: { color: 'primary', icon: 'ri-filter-3-line', label: 'احراز صلاحیت' },
  need_discovery: { color: 'secondary', icon: 'ri-search-eye-line', label: 'کشف نیاز' },
  product_demo: { color: 'warning', icon: 'ri-projector-line', label: 'نمایش محصول' },
  commercial_proposal: { color: 'success', icon: 'ri-file-list-3-line', label: 'پیشنهاد تجاری' },
  negotiate_close: { color: 'error', icon: 'ri-handshake-line', label: 'مذاکره و بستن' },
}

/** انواع فعالیت */
export const ACTIVITY_VISUAL: Record<string, VisualTone> = {
  call: { color: 'success', icon: 'ri-phone-line', label: 'تماس' },
  meeting: { color: 'primary', icon: 'ri-group-line', label: 'جلسه' },
  message: { color: 'info', icon: 'ri-mail-line', label: 'ایمیل / پیام' },
  visit: { color: 'warning', icon: 'ri-map-pin-line', label: 'بازدید' },
  proposal: { color: 'secondary', icon: 'ri-file-paper-2-line', label: 'ارسال پیشنهاد' },
  note: { color: 'default', icon: 'ri-sticky-note-line', label: 'یادداشت' },
  referral: { color: 'purple', icon: 'ri-share-forward-line', label: 'ارجاع' },
  other: { color: 'default', icon: 'ri-more-line', label: 'سایر' },
}

const FALLBACK: VisualTone = { color: 'default', icon: 'ri-circle-line' }

export function stageVisual(codeOrName?: string | null): VisualTone {
  if (!codeOrName) return FALLBACK
  const key = codeOrName.trim()
  if (STAGE_VISUAL[key]) return STAGE_VISUAL[key]
  // match by Persian name fragment
  const found = Object.values(STAGE_VISUAL).find(v => v.label && key.includes(v.label))
  return found || FALLBACK
}

export function activityVisual(type?: string | null): VisualTone {
  if (!type) return FALLBACK
  return ACTIVITY_VISUAL[type] || FALLBACK
}

/** رنگ نوار کناری فعالیت بر اساس نوع */
export function activityBarColor(type?: string | null): string {
  const map: Record<string, string> = {
    call: '#43a047',
    meeting: '#1e88e5',
    message: '#00acc1',
    visit: '#fb8c00',
    proposal: '#8e24aa',
    note: '#78909c',
    referral: '#5e35b1',
    other: '#90a4ae',
  }
  return map[type || ''] || '#90a4ae'
}
