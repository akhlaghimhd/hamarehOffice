<script setup>
import { cn } from '@/lib/utils';
import { buttonVariants } from '@/components/ui/button';

defineProps({
    type: {
        type: String,
        default: 'button',
    },
    class: {
        type: [String, Object, Array],
        default: '',
    },
});
</script>

<template>
    <button
        :type="type"
        :class="cn(buttonVariants({ variant: 'destructive', size: 'default' }), $props.class)"
    >
        <slot />
    </button>
</template>
