import type { App } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from './routes'
import { getToken } from '@/utils/api'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

const publicPaths = new Set([
  '/login',
  '/register',
  '/forgot-password',
])

router.beforeEach((to) => {
  const token = getToken()
  const isPublic = publicPaths.has(to.path)

  if (!token && !isPublic)
    return { path: '/login', query: { redirect: to.fullPath } }

  if (token && to.path === '/login')
    return { path: '/dashboard' }

  return true
})

export default function (app: App) {
  app.use(router)
}

export { router }
