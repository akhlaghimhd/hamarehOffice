<script setup lang="ts">
const orgs = ['صنایع غذایی آفتاب', 'پتروشیمی پارس', 'فناوری نوآور', 'معدن شرق', 'خودروسازان']
const selected = ref(null)
const multi = ref([])
const combo = ref(null)
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="Autocomplete / Combobox">
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VAutocomplete v-model="selected" :items="orgs" label="جستجوی سازمان" clearable />
          </VCol>
          <VCol cols="12" md="6">
            <VAutocomplete v-model="multi" :items="orgs" label="چندانتخابی" multiple chips closable-chips />
          </VCol>
          <VCol cols="12" md="6">
            <VCombobox v-model="combo" :items="orgs" label="Combobox (مقدار آزاد + لیست)" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
