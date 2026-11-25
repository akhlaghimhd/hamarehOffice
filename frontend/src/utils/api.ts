const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8088'

const TOKEN_KEY = 'hamareh_token'
const USER_KEY = 'hamareh_user'
const COMPANY_KEY = 'hamareh_company'
const COMPANIES_KEY = 'hamareh_companies'

export type CompanyOption = {
  id: number
  name: string
  code?: string | null
  is_current?: boolean
}

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setAuth(token: string, user: unknown, companies?: CompanyOption[]) {
  localStorage.setItem(TOKEN_KEY, token)
  localStorage.setItem(USER_KEY, JSON.stringify(user))
  if (companies && companies.length)
    setCompanies(companies)
}

export function setCompanies(companies: CompanyOption[]) {
  localStorage.setItem(COMPANIES_KEY, JSON.stringify(companies))
  const current = getCompany()
  const stillValid = current && companies.some(c => c.id === current.id)
  if (!stillValid) {
    const next = companies.find(c => c.is_default) || companies[0]
    if (next)
      localStorage.setItem(COMPANY_KEY, JSON.stringify(next))
  }
}

export function clearAuth() {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
  localStorage.removeItem(COMPANY_KEY)
  localStorage.removeItem(COMPANIES_KEY)
}

export function getUser<T = unknown>(): T | null {
  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null
  try { return JSON.parse(raw) as T } catch { return null }
}

export function getCompany(): CompanyOption | null {
  const raw = localStorage.getItem(COMPANY_KEY)
  if (!raw) return null
  try { return JSON.parse(raw) as CompanyOption } catch { return null }
}

export function getCompanies(): CompanyOption[] {
  const raw = localStorage.getItem(COMPANIES_KEY)
  if (!raw) return []
  try { return JSON.parse(raw) as CompanyOption[] } catch { return [] }
}

export function setCompany(company: CompanyOption) {
  localStorage.setItem(COMPANY_KEY, JSON.stringify(company))
}

export async function api<T = unknown>(
  path: string,
  options: RequestInit = {},
  withCompany = true,
): Promise<T> {
  const headers = new Headers(options.headers || {})
  headers.set('Accept', 'application/json')
  if (!(options.body instanceof FormData))
    headers.set('Content-Type', 'application/json')

  const token = getToken()
  if (token) headers.set('Authorization', `Bearer ${token}`)

  if (withCompany) {
    const company = getCompany()
    if (company?.id)
      headers.set('X-Company-Id', String(company.id))
  }

  const res = await fetch(`${API_URL}${path}`, { ...options, headers })
  const data = await res.json().catch(() => ({}))

  if (!res.ok) {
    const message =
      (data as any)?.message ||
      (data as any)?.errors?.email?.[0] ||
      `HTTP ${res.status}`
    throw new Error(message)
  }
  return data as T
}

export async function loginRequest(email: string, password: string) {
  return api<{ token: string; user: { id: number; name: string; email: string }; companies: CompanyOption[] }>(
    '/api/login',
    { method: 'POST', body: JSON.stringify({ email, password }) },
    false,
  )
}

export async function fetchCompanies() {
  return api<{ data: CompanyOption[] }>('/api/companies', {}, false)
}

export async function switchCompanyRequest(companyId: number) {
  return api<{ company: CompanyOption }>('/api/companies/switch', {
    method: 'POST',
    body: JSON.stringify({ company_id: companyId }),
  }, false)
}

export async function fetchCompanyUsers() {
  return api<{ data: { id: number; name: string; email?: string }[] }>('/api/company-users')
}

export async function fetchParties() {
  return api<{ data: any[] }>('/api/parties')
}

export async function createParty(payload: Record<string, unknown>) {
  return api<{ data: any }>('/api/parties', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function fetchOpportunities(query = '') {
  const q = query ? `?${query}` : ''
  return api<{ data: any[] }>(`/api/opportunities${q}`)
}

export async function createOpportunity(payload: Record<string, unknown>) {
  return api<{ data: any }>('/api/opportunities', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function fetchOpportunity(id: number) {
  return api<{ data: any }>(`/api/opportunities/${id}`)
}

export async function updateOpportunity(id: number, payload: Record<string, unknown>) {
  return api<{ data: any }>(`/api/opportunities/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export async function closeOpportunityStep(id: number, payload: Record<string, unknown>) {
  return api<{ data: any }>(`/api/opportunities/${id}/close-step`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function createOpportunityActivity(id: number, payload: Record<string, unknown>) {
  return api<{ data: any }>(`/api/opportunities/${id}/activities`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function assignOpportunity(id: number, payload: Record<string, unknown>) {
  return api<{ data: any }>(`/api/opportunities/${id}/assign`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function fetchOpportunityStages() {
  return api<{ data: any[] }>('/api/opportunity-stages')
}
