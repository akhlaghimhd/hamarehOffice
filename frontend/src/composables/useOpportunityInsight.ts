import { fetchOpportunityInsight } from '@/utils/api'

export type OpportunityInsight = {
  bullets: string[]
  suggestion: string
  score_label: string
  score_tone: 'ok' | 'warn' | 'danger'
  source?: 'local' | 'llm'
  llm_available?: boolean
  llm_error?: boolean
}

export function useOpportunityInsight(opportunityId: () => number) {
  const insight = ref<OpportunityInsight | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function loadInsight() {
    loading.value = true
    error.value = null
    try {
      // مدل لوکال گاهی تا یک دقیقه طول می‌کشد
      const res = await Promise.race([
        fetchOpportunityInsight(opportunityId()),
        new Promise<never>((_, reject) => {
          window.setTimeout(() => reject(new Error('insight-timeout')), 90000)
        }),
      ])

      // پشتیبانی از { data: {...} } و خود آبجکت
      const payload = (res as any)?.data ?? res
      if (payload && typeof payload === 'object') {
        insight.value = {
          bullets: Array.isArray(payload.bullets) ? payload.bullets : [],
          suggestion: String(payload.suggestion || ''),
          score_label: String(payload.score_label || 'پایدار'),
          score_tone: (payload.score_tone === 'danger' || payload.score_tone === 'warn')
            ? payload.score_tone
            : 'ok',
          source: payload.source === 'llm' ? 'llm' : 'local',
          llm_available: Boolean(payload.llm_available),
          llm_error: Boolean(payload.llm_error),
        }
      }
    }
    catch (e: any) {
      console.warn('[insight] load failed:', e?.message || e)
      error.value = e?.message || 'insight-failed'
      // null نکن اگر قبلاً داده داشتیم
    }
    finally {
      loading.value = false
    }
  }

  return { insight, loading, error, loadInsight }
}
