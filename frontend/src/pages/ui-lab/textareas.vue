<script setup lang="ts">
const note = ref('')
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="Textarea">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VTextarea v-model="note" label="یادداشت" placeholder="متن چندخطی" rows="3" />
          </VCol>
          <VCol cols="12" md="6">
            <VTextarea label="رشد خودکار" auto-grow rows="2" placeholder="با تایپ بزرگ می‌شود" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
