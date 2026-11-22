<script lang="ts" setup>
import VerticalNavSectionTitle from '@/@layouts/components/VerticalNavSectionTitle.vue'
import VerticalNavLink from '@layouts/components/VerticalNavLink.vue'
</script>

<template>
  <VerticalNavLink
    :item="{
      title: 'داشبورد',
      icon: 'ri-home-smile-line',
      to: '/dashboard',
    }"
  />

  <VerticalNavSectionTitle
    :item="{
      heading: 'عملیات',
    }"
  />

  <VerticalNavLink
    :item="{
      title: 'مشتریان',
      icon: 'ri-user-star-line',
      to: '/dashboard',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'فرصت‌ها',
      icon: 'ri-funds-box-line',
      to: '/dashboard',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'کاتالوگ',
      icon: 'ri-store-2-line',
      to: '/dashboard',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'سفارش‌ها',
      icon: 'ri-shopping-bag-3-line',
      to: '/dashboard',
    }"
  />

  <VerticalNavSectionTitle
    :item="{
      heading: 'سازمان',
    }"
  />

  <VerticalNavLink
    :item="{
      title: 'شرکت',
      icon: 'ri-building-4-line',
      to: '/dashboard',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'تنظیمات حساب',
      icon: 'ri-user-settings-line',
      to: '/account-settings',
    }"
  />

  <VerticalNavSectionTitle
    :item="{
      heading: 'نمونه UI',
    }"
  />

  <VerticalNavLink
    :item="{
      title: 'کارت‌ها',
      icon: 'ri-bar-chart-box-line',
      to: '/cards',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'جداول',
      icon: 'ri-table-line',
      to: '/tables',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'فرم‌ها',
      icon: 'ri-layout-4-line',
      to: '/form-layouts',
    }"
  />
</template>
