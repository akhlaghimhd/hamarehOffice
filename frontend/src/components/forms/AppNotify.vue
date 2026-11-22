<script setup lang="ts">
import { useNotify } from '@/composables/useNotify'

const { state } = useNotify()
</script>

<template>
  <VSnackbar
    v-model="state.show"
    :color="state.color"
    :timeout="state.timeout"
    location="top"
    multi-line
  >
    {{ state.message }}
    <template #actions>
      <VBtn
        variant="text"
        @click="state.show = false"
      >
        بستن
      </VBtn>
    </template>
  </VSnackbar>
</template>
