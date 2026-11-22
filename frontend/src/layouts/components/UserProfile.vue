<script setup lang="ts">
import { useRouter } from 'vue-router'
import avatar1 from '@images/avatars/avatar-1.png'
import { clearAuth, getUser } from '@/utils/api'

const router = useRouter()
const user = computed(() => getUser<{ name?: string; email?: string }>())

function logout() {
  clearAuth()
  router.push('/login')
}
</script>

<template>
  <VBadge
    dot
    location="bottom right"
    offset-x="3"
    offset-y="3"
    color="success"
    bordered
  >
    <VAvatar
      class="cursor-pointer"
      color="primary"
      variant="tonal"
    >
      <VImg :src="avatar1" />

      <VMenu
        activator="parent"
        width="240"
        location="bottom end"
        offset="14px"
      >
        <VList>
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                >
                  <VAvatar
                    color="primary"
                    variant="tonal"
                  >
                    <VImg :src="avatar1" />
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-semibold">
              {{ user?.name || 'کاربر' }}
            </VListItemTitle>
            <VListItemSubtitle>
              {{ user?.email || '—' }}
            </VListItemSubtitle>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem to="/account-settings">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="ri-user-settings-line"
                size="22"
              />
            </template>
            <VListItemTitle>تنظیمات حساب</VListItemTitle>
          </VListItem>

          <VDivider class="my-2" />

          <VListItem @click="logout">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="ri-logout-box-r-line"
                size="22"
              />
            </template>
            <VListItemTitle>خروج</VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
    </VAvatar>
  </VBadge>
</template>
