import { fetchOpportunityInsight } from '@/utils/api'

export type OpportunityInsight = {
  bullets: string[]
  suggestion: string
  score_label: string
  score_tone: 'ok' | 'warn' | 'danger'
  source?: 'local'
}

export function useOpportunityInsight(opportunityId: () => number) {
  const insight = ref<OpportunityInsight | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function loadInsight() {
    loading.value = true
    error.value = null
    try {
      const res = await Promise.race([
        fetchOpportunityInsight(opportunityId()),
        new Promise<never>((_, reject) => {
          window.setTimeout(() => reject(new Error('insight-timeout')), 8000)
        }),
      ])

      const payload = (res as any)?.data ?? res
      if (payload && typeof payload === 'object') {
        insight.value = {
          bullets: Array.isArray(payload.bullets) ? payload.bullets : [],
          suggestion: String(payload.suggestion || ''),
          score_label: String(payload.score_label || 'پایدار'),
          score_tone: (payload.score_tone === 'danger' || payload.score_tone === 'warn')
            ? payload.score_tone
            : 'ok',
          source: 'local',
        }
      }
    }
    catch (e: any) {
      console.warn('[insight] load failed:', e?.message || e)
      error.value = e?.message || 'insight-failed'
    }
    finally {
      loading.value = false
    }
  }

  return { insight, loading, error, loadInsight }
}
