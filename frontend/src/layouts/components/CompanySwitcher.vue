<script setup lang="ts">
import { getCompanies, getCompany, setCompany, switchCompanyRequest, type CompanyOption } from '@/utils/api'

const companies = ref<CompanyOption[]>(getCompanies())
const current = ref<CompanyOption | null>(getCompany())
const loading = ref(false)

async function onSelect(company: CompanyOption) {
  if (!company || company.id === current.value?.id) return
  loading.value = true
  try {
    const res = await switchCompanyRequest(company.id)
    setCompany(res.company)
    current.value = res.company
    companies.value = getCompanies().map(c => ({
      ...c,
      is_default: c.id === res.company.id,
    }))
    localStorage.setItem('hamareh_companies', JSON.stringify(companies.value))
  }
  catch (e: any) {
    console.error(e)
  }
  finally {
    loading.value = false
  }
}
</script>

<template>
  <VMenu location="bottom">
    <template #activator="{ props }">
      <VBtn
        v-bind="props"
        variant="tonal"
        color="primary"
        class="me-2"
        :loading="loading"
        prepend-icon="ri-building-4-line"
      >
        <span class="text-truncate" style="max-inline-size: 12rem;">
          {{ current?.name || 'انتخاب شرکت' }}
        </span>
      </VBtn>
    </template>

    <VList density="compact" min-width="220">
      <VListSubheader>شرکت جاری</VListSubheader>
      <VListItem
        v-for="c in companies"
        :key="c.id"
        :active="c.id === current?.id"
        @click="onSelect(c)"
      >
        <VListItemTitle>{{ c.name }}</VListItemTitle>
        <VListItemSubtitle v-if="c.code">{{ c.code }}</VListItemSubtitle>
      </VListItem>
      <VListItem v-if="!companies.length">
        <VListItemTitle class="text-disabled">شرکتی یافت نشد</VListItemTitle>
      </VListItem>
    </VList>
  </VMenu>
</template>
