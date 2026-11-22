<script setup lang="ts">
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

const confirmOpen = ref(false)

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
    location="end"
    temporary
    :width="width"
    class="form-drawer"
    @update:model-value="onDrawerUpdate"
  >
    <div class="d-flex flex-column h-100">
      <div class="pa-4 d-flex align-start justify-space-between gap-2 border-b">
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
          @click="requestClose"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </div>

      <div class="flex-grow-1 overflow-y-auto pa-4">
        <slot />
      </div>

      <div
        v-if="$slots.actions"
        class="pa-4 border-t d-flex flex-wrap justify-end gap-2"
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
