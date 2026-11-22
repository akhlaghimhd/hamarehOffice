<script setup lang="ts">
const single = ref('')
const multi = ref<string[]>([])
const items = ['سرنخ اولیه', 'کشف نیاز', 'پیشنهاد', 'مذاکره']
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="Select">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VSelect v-model="single" :items="items" label="تک‌انتخابی" placeholder="انتخاب کنید" />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect v-model="multi" :items="items" label="چندانتخابی" multiple chips closable-chips />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect :items="items" label="غیرفعال" disabled />
          </VCol>
          <VCol cols="12" md="6">
            <VSelect :items="items" label="فشرده" density="compact" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
