<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-muted/30">
        <nav class="border-b border-border bg-card">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center justify-between gap-4">
                    <div class="flex items-center gap-6">
                        <Link
                            :href="route('dashboard')"
                            class="flex items-center gap-2 font-bold text-foreground"
                        >
                            <ApplicationLogo
                                class="h-8 w-auto fill-current text-foreground"
                            />
                            <span class="hidden sm:inline">هم‌ره</span>
                        </Link>

                        <div class="hidden sm:flex sm:gap-1">
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                داشبورد
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center">
                        <Dropdown align="left" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md border border-border bg-background px-3 py-1.5 text-sm font-medium text-foreground shadow-sm transition hover:bg-accent"
                                >
                                    {{ $page.props.auth.user.name }}
                                    <svg
                                        class="h-4 w-4 text-muted-foreground"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    پروفایل
                                </DropdownLink>
                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    خروج
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-muted-foreground transition hover:bg-accent hover:text-foreground"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="border-t border-border sm:hidden"
            >
                <div class="space-y-1 px-3 py-2">
                    <ResponsiveNavLink
                        :href="route('dashboard')"
                        :active="route().current('dashboard')"
                    >
                        داشبورد
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-border px-3 py-3">
                    <div class="text-sm font-medium text-foreground">
                        {{ $page.props.auth.user.name }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ $page.props.auth.user.email }}
                    </div>
                    <div class="mt-2 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            پروفایل
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                        >
                            خروج
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header
            v-if="$slots.header"
            class="border-b border-border bg-card"
        >
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
