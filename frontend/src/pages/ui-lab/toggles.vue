<script setup lang="ts">
const checks = ref(['a'])
const radio = ref('1')
const sw = ref(true)
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VRow>
      <VCol cols="12" md="4">
        <VCard title="Checkbox">
          <VCardText>
            <VCheckbox v-model="checks" label="گزینه الف" value="a" />
            <VCheckbox v-model="checks" label="گزینه ب" value="b" />
            <VCheckbox label="غیرفعال" disabled />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard title="Radio">
          <VCardText>
            <VRadioGroup v-model="radio">
              <VRadio label="فعال" value="1" />
              <VRadio label="متوقف" value="2" />
              <VRadio label="بسته" value="3" />
            </VRadioGroup>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard title="Switch">
          <VCardText class="d-flex flex-column gap-2">
            <VSwitch v-model="sw" label="فعال‌سازی" />
            <VSwitch label="با رنگ" color="success" model-value />
            <VSwitch label="غیرفعال" disabled />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
