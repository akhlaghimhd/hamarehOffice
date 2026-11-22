<script setup lang="ts">
import { formatRialFa, onlyDigits } from '@/utils/fa'
import { rules } from '@/utils/formRules'

/** v-model = مقدار خام ریال بدون جداکننده (string) */
const model = defineModel<string>({ default: '' })

const props = withDefaults(defineProps<{
  label?: string
  required?: boolean
}>(), {
  label: 'مبلغ (ریال)',
  required: false,
})

const display = computed({
  get: () => formatRialFa(model.value),
  set: (v: string) => {
    model.value = onlyDigits(v)
  },
})

const fieldRules = computed(() => {
  const list: any[] = []
  if (props.required)
    list.push(rules.required(props.label))
  list.push(rules.rial)
  return list
})
</script>

<template>
  <VTextField
    v-model="display"
    :label="label"
    type="text"
    inputmode="numeric"
    :rules="fieldRules"
    hint="مبالغ به ریال — جداکننده خودکار"
    persistent-hint
    dir="ltr"
    class="text-end"
  />
</template>
