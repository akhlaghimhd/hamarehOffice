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

  async function loadInsight() {
    loading.value = true
    try {
      // Local models can be slow; never hang the UI forever
      const res = await Promise.race([
        fetchOpportunityInsight(opportunityId()),
        new Promise<never>((_, reject) => {
          window.setTimeout(() => reject(new Error('insight-timeout')), 25000)
        }),
      ])
      insight.value = res.data
    }
    catch {
      // Keep previous/local fallback in the page
      if (!insight.value)
        insight.value = null
    }
    finally {
      loading.value = false
    }
  }

  return { insight, loading, loadInsight }
}
