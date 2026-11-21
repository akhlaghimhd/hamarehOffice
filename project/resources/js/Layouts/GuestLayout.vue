<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-muted/40 px-4 py-10"
    >
        <div class="mb-8 text-center">
            <Link
                href="/"
                class="text-2xl font-bold tracking-tight text-foreground"
            >
                هم‌ره
            </Link>
            <p class="mt-1 text-sm text-muted-foreground">
                سامانه عملیاتی چندشرکتی
            </p>
        </div>

        <div
            class="w-full max-w-md rounded-xl border border-border bg-card p-6 text-card-foreground shadow-sm sm:p-8"
        >
            <slot />
        </div>
    </div>
</template>
