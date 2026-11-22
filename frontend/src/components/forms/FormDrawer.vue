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

watch(
  () => props.modelValue,
  (open, was) => {
    if (open && !was)
      lock()
    if (!open && was)
      unlock()
  },
)

onBeforeUnmount(() => {
  if (props.modelValue)
    unlock()
})

function tryClose() {
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

/** فقط وقتی واقعاً باید بسته شود model را false کن */
function onOverlayUpdate(v: boolean) {
  if (v) {
    emit('update:modelValue', true)
    return
  }
  tryClose()
}
</script>

<template>
  <!-- موبایل: دیالوگ تمام‌صفحه (قابل اعتمادتر از NavigationDrawer) -->
  <VDialog
    v-if="mobile"
    :model-value="modelValue"
    fullscreen
    transition="dialog-bottom-transition"
    :scrim="true"
    @update:model-value="onOverlayUpdate"
  >
    <VCard class="form-sheet d-flex flex-column h-100 rounded-0">
      <div class="form-sheet__header pa-4 d-flex align-start justify-space-between gap-2">
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
          @click="tryClose"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </div>

      <div class="form-sheet__body flex-grow-1 overflow-y-auto pa-4">
        <slot />
      </div>

      <div
        v-if="$slots.actions"
        class="form-sheet__actions pa-4 d-flex flex-wrap justify-end gap-2"
      >
        <slot name="actions" />
      </div>
    </VCard>
  </VDialog>

  <!-- دسکتاپ: دراور راست -->
  <VNavigationDrawer
    v-else
    :model-value="modelValue"
    location="right"
    temporary
    :width="width"
    @update:model-value="onOverlayUpdate"
  >
    <div class="form-sheet d-flex flex-column h-100">
      <div class="form-sheet__header pa-4 d-flex align-start justify-space-between gap-2">
        <div class="d-flex align-start gap-3">
          <VImg
            v-if="illustration"
            :src="illustration"
            width="56"
            height="56"
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
          @click="tryClose"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </div>

      <div class="form-sheet__body flex-grow-1 overflow-y-auto pa-4">
        <slot />
      </div>

      <div
        v-if="$slots.actions"
        class="form-sheet__actions pa-4 d-flex flex-wrap justify-end gap-2"
      >
        <slot name="actions" />
      </div>
    </div>
  </VNavigationDrawer>

  <VDialog
    v-model="confirmOpen"
    max-width="400"
    persistent
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
.form-sheet__header {
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  flex-shrink: 0;
}

.form-sheet__actions {
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  flex-shrink: 0;
  background: rgb(var(--v-theme-surface));
  position: sticky;
  bottom: 0;
  z-index: 2;
  padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px)) !important;
}
</style>
