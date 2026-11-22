<script setup lang="ts">
import { onlyDigits, toFaDigits } from '@/utils/fa'
import { rules } from '@/utils/formRules'

const model = defineModel<string>({ default: '' })

const props = withDefaults(defineProps<{
  label?: string
  mobile?: boolean
  required?: boolean
}>(), {
  label: 'تلفن',
  mobile: false,
  required: false,
})

const display = computed({
  get: () => toFaDigits(model.value || ''),
  set: (v: string) => {
    model.value = onlyDigits(v).slice(0, 11)
  },
})

const fieldRules = computed(() => {
  const list: any[] = []
  if (props.required)
    list.push(rules.required(props.label))
  list.push(props.mobile ? rules.mobile09 : rules.phone11)
  return list
})
</script>

<template>
  <VTextField
    v-model="display"
    :label="label"
    type="tel"
    inputmode="numeric"
    autocomplete="tel"
    :rules="fieldRules"
    maxlength="11"
  />
</template>
