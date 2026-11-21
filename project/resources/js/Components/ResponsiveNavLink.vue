<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full rounded-md bg-accent px-3 py-2 text-start text-sm font-medium text-accent-foreground'
        : 'block w-full rounded-md px-3 py-2 text-start text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-accent-foreground',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
