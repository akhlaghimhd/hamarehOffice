<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="داشبورد" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-foreground">داشبورد</h1>
        </template>

        <div
            class="rounded-xl border border-border bg-card p-6 text-card-foreground shadow-sm"
        >
            <p class="text-sm text-muted-foreground">
                با موفقیت وارد شدید. از اینجا توسعه ماژول‌ها آغاز می‌شود.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
