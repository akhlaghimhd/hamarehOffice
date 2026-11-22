<script setup lang="ts">
import { useDisplay } from 'vuetify'
import { useMobileChrome } from '@/composables/useMobileChrome'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title: string
  subtitle?: string
  width?: string | number
  dirty?: boolean
  illustration?: string
}>(), {
  width: 480,
  dirty: false,
})

const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void }>()

const { mobile } = useDisplay()
const { lock, unlock } = useMobileChrome()
const confirmOpen = ref(false)

const drawerWidth = computed(() => (mobile.value ? '100%' : props.width))

watch(
  () => props.modelValue,
  (open, wasOpen) => {
    if (open && !wasOpen)
      lock()
    if (!open && wasOpen)
      unlock()
  },
)

onBeforeUnmount(() => {
  if (props.modelValue)
    unlock()
})

function requestClose() {
  if (props.dirty) {
    confirmOpen.value = true
    return
  }
  emit('update:modelValue', false)
}

function forceClose() {
  confirmOpen.value = false
  emit('update:modelValue', false)
}

function onDrawerUpdate(v: boolean) {
  if (!v)
    requestClose()
  else
    emit('update:modelValue', true)
}
</script>

<template>
  <VNavigationDrawer
    :model-value="modelValue"
    location="right"
    temporary
    :width="drawerWidth"
    :scrim="true"
    class="form-drawer"
    @update:model-value="onDrawerUpdate"
  >
    <div class="form-drawer__inner d-flex flex-column h-100">
      <div class="form-drawer__header pa-4 d-flex align-start justify-space-between gap-2">
        <div class="d-flex align-start gap-3">
          <VImg
            v-if="illustration"
            :src="illustration"
            width="48"
            height="48"
            class="flex-shrink-0"
          />
          <div>
            <div class="text-h6">
              {{ title }}
            </div>
            <div
              v-if="subtitle"
              class="text-body-2 text-medium-emphasis"
            >
              {{ subtitle }}
            </div>
          </div>
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="requestClose"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </div>

      <div class="form-drawer__body flex-grow-1 overflow-y-auto pa-4">
        <slot />
      </div>

      <div
        v-if="$slots.actions"
        class="form-drawer__actions pa-4 d-flex flex-wrap justify-end gap-2"
      >
        <slot name="actions" />
      </div>
    </div>
  </VNavigationDrawer>

  <VDialog
    v-model="confirmOpen"
    max-width="400"
  >
    <VCard title="تغییرات ذخیره نشده">
      <VCardText>
        فرم تغییر کرده است. بدون ذخیره خارج می‌شوید؟
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn
          variant="text"
          @click="confirmOpen = false"
        >
          ادامه ویرایش
        </VBtn>
        <VBtn
          color="error"
          @click="forceClose"
        >
          خروج بدون ذخیره
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.form-drawer :deep(.v-navigation-drawer__content) {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.form-drawer__inner {
  min-height: 100%;
}

.form-drawer__header {
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  flex-shrink: 0;
}

.form-drawer__actions {
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  flex-shrink: 0;
  background: rgb(var(--v-theme-surface));
  position: sticky;
  bottom: 0;
  z-index: 2;
  /* فاصله از home indicator موبایل */
  padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px)) !important;
}
</style>
