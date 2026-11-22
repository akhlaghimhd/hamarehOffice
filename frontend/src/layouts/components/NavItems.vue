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

  <VerticalNavSectionTitle :item="{ heading: 'عملیات' }" />

  <VerticalNavLink
    :item="{
      title: 'طرف‌های تجاری',
      icon: 'ri-user-star-line',
      to: '/parties',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'فرصت‌ها',
      icon: 'ri-funds-box-line',
      to: '/opportunities',
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

  <VerticalNavSectionTitle :item="{ heading: 'سازمان' }" />

  <VerticalNavLink
    :item="{
      title: 'تنظیمات حساب',
      icon: 'ri-user-settings-line',
      to: '/account-settings',
    }"
  />

  <!-- موقت: پس از تثبیت UI حذف شود -->
  <VerticalNavSectionTitle :item="{ heading: 'الگوهای UI (موقت)' }" />
  <VerticalNavLink
    :item="{
      title: 'فهرست الگوها',
      icon: 'ri-palette-line',
      to: '/ui-lab',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'دکمه‌ها',
      icon: 'ri-checkbox-blank-line',
      to: '/ui-lab/buttons',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'تب‌ها',
      icon: 'ri-layout-left-line',
      to: '/ui-lab/tabs',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'فرم Text field',
      icon: 'ri-input-field',
      to: '/ui-lab/textfields',
    }"
  />
  <VerticalNavLink
    :item="{
      title: 'دیالوگ',
      icon: 'ri-window-line',
      to: '/ui-lab/dialogs',
    }"
  />
</template>
