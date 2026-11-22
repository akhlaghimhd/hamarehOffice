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
      heading: 'حساب',
    }"
  />

  <VerticalNavLink
    :item="{
      title: 'ورود',
      icon: 'ri-login-box-line',
      to: '/login',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'ثبت‌نام',
      icon: 'ri-user-add-line',
      to: '/register',
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
      heading: 'نمونه اجزا',
    }"
  />

  <VerticalNavLink
    :item="{
      title: 'تایپوگرافی',
      icon: 'ri-text',
      to: '/typography',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'آیکون‌ها',
      icon: 'ri-remixicon-line',
      to: '/icons',
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
      title: 'فرم‌ها',
      icon: 'ri-layout-4-line',
      to: '/form-layouts',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'جداول',
      icon: 'ri-table-line',
      to: '/tables',
    }"
  />
</template>
