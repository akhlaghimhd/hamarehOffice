<script setup lang="ts">
const panel = ref(['optional'])
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" size="small" class="px-0 mb-4" prepend-icon="ri-arrow-right-line" :to="{ path: '/ui-lab' }">الگوهای UI</VBtn>
    <VCard title="Expansion — بخش‌های اختیاری فرم">
      <VCardText>
        <VExpansionPanels v-model="panel" multiple>
          <VExpansionPanel value="optional" title="بخش اختیاری (پیش‌فرض باز)">
            <VExpansionPanelText>
              محتوای فیلدهای اختیاری مثل ایمیل و آدرس.
            </VExpansionPanelText>
          </VExpansionPanel>
          <VExpansionPanel value="meta" title="متا داده">
            <VExpansionPanelText>بودجه، تیراژ، ارزش مشتری...</VExpansionPanelText>
          </VExpansionPanel>
          <VExpansionPanel value="notes" title="یادداشت">
            <VExpansionPanelText>یادداشت شروع و منبع سرنخ</VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      </VCardText>
    </VCard>
  </div>
</template>
