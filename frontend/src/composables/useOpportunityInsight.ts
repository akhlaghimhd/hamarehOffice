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
      const res = await fetchOpportunityInsight(opportunityId())
      insight.value = res.data
    }
    catch {
      insight.value = null
    }
    finally {
      loading.value = false
    }
  }

  return { insight, loading, loadInsight }
}
