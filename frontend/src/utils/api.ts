const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8088'

const TOKEN_KEY = 'hamareh_token'
const USER_KEY = 'hamareh_user'

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setAuth(token: string, user: unknown) {
  localStorage.setItem(TOKEN_KEY, token)
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export function clearAuth() {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
}

export function getUser<T = unknown>(): T | null {
  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null
  try {
    return JSON.parse(raw) as T
  } catch {
    return null
  }
}

export async function api<T = unknown>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const headers = new Headers(options.headers || {})
  headers.set('Accept', 'application/json')
  if (!(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }
  const token = getToken()
  if (token) headers.set('Authorization', `Bearer ${token}`)

  const res = await fetch(`${API_URL}${path}`, {
    ...options,
    headers,
  })

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
  return api<{ token: string; user: { id: number; name: string; email: string } }>(
    '/api/login',
    {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    },
  )
}
