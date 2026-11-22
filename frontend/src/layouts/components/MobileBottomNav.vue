<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useMobileChrome } from '@/composables/useMobileChrome'

const route = useRoute()
const router = useRouter()
const { bottomNavVisible } = useMobileChrome()

const items = [
  { title: 'خانه', icon: 'ri-home-smile-line', to: '/dashboard' },
  { title: 'فرصت‌ها', icon: 'ri-funds-box-line', to: '/opportunities' },
  { title: 'طرف‌ها', icon: 'ri-user-star-line', to: '/parties' },
  { title: 'حساب', icon: 'ri-user-settings-line', to: '/account-settings' },
]

function isActive(to: string) {
  if (to === '/dashboard')
    return route.path === '/dashboard' || route.path === '/'
  return route.path === to || route.path.startsWith(to + '/')
}

function go(to: string) {
  router.push(to)
}
</script>

<template>
  <nav
    v-show="bottomNavVisible"
    class="mobile-bottom-nav d-lg-none"
  >
    <button
      v-for="item in items"
      :key="item.to"
      type="button"
      class="mobile-bottom-nav__item"
      :class="{ 'mobile-bottom-nav__item--active': isActive(item.to) }"
      @click="go(item.to)"
    >
      <VIcon
        :icon="item.icon"
        size="22"
      />
      <span>{{ item.title }}</span>
    </button>
  </nav>
</template>

<style scoped>
.mobile-bottom-nav {
  position: fixed;
  inset-inline: 0;
  bottom: 0;
  z-index: 1005;
  display: flex;
  align-items: stretch;
  justify-content: space-around;
  min-height: 56px;
  padding-bottom: env(safe-area-inset-bottom, 0);
  background: rgb(var(--v-theme-surface));
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  box-shadow: 0 -4px 16px rgba(var(--v-shadow-key-umbra-color), 0.08);
}

.mobile-bottom-nav__item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  min-width: 0;
  padding: 6px 4px;
  border: 0;
  background: transparent;
  color: rgba(var(--v-theme-on-surface), 0.55);
  font-size: 0.7rem;
  line-height: 1.2;
  cursor: pointer;
}

.mobile-bottom-nav__item--active {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
}
</style>
