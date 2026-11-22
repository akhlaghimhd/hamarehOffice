<script setup lang="ts">
import {
  fetchCompanies,
  getCompanies,
  getCompany,
  setCompanies,
  setCompany,
  switchCompanyRequest,
  type CompanyOption,
} from '@/utils/api'

const companies = ref<CompanyOption[]>(getCompanies())
const current = ref<CompanyOption | null>(getCompany())
const loading = ref(false)

async function hydrate() {
  loading.value = true
  try {
    const res = await fetchCompanies()
    const list = res.data || []
    setCompanies(list)
    companies.value = list
    current.value = getCompany()
  }
  catch (e) {
    console.error(e)
  }
  finally {
    loading.value = false
  }
}

onMounted(() => {
  hydrate()
})

async function onSelect(company: CompanyOption) {
  if (!company || company.id === current.value?.id) return
  loading.value = true
  try {
    const res = await switchCompanyRequest(company.id)
    setCompany(res.company)
    current.value = res.company
    companies.value = companies.value.map(c => ({
      ...c,
      is_default: c.id === res.company.id,
    }))
    setCompanies(companies.value)
    // reload page data that depends on company
    window.location.reload()
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
        <span class="text-truncate" style="max-inline-size: 14rem;">
          {{ current?.name || 'انتخاب شرکت' }}
        </span>
      </VBtn>
    </template>

    <VList density="compact" min-width="240">
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
