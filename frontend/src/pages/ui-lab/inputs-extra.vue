<script setup lang="ts">
const file = ref(null)
const range = ref([20, 60])
const slider = ref(40)
const rating = ref(4)
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VRow>
      <VCol cols="12" md="6">
        <VCard title="آپلود فایل">
          <VCardText>
            <VFileInput v-model="file" label="پیوست" prepend-icon="ri-attachment-2" show-size />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard title="امتیاز">
          <VCardText>
            <VRating v-model="rating" color="warning" />
            <div class="text-body-2 mt-2">{{ rating }} از ۵</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard title="اسلایدر">
          <VCardText>
            <VSlider v-model="slider" thumb-label color="primary" />
            <div class="text-body-2">{{ slider }}٪</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard title="بازه (Range)">
          <VCardText>
            <VRangeSlider v-model="range" thumb-label color="primary" />
            <div class="text-body-2">{{ range[0] }} — {{ range[1] }}</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
