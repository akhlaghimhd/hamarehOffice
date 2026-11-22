# وضعیت پروژه

**به‌روزرسانی:** 2026-11-22

## استک مصوب UI
- **Materio Free + Vue 3 + Vuetify 3 + Vue Router**
- Laravel به‌عنوان backend/API
- **لغو مسیر قبلی:** Inertia + Tailwind به‌عنوان سیستم اصلی + shadcn-vue
- Filament ممنوع

## انجام‌شده
- مخزن و اسناد پایه
- اسکلت اولیه Laravel (بعداً UI آن کنار گذاشته می‌شود)
- Docker MariaDB لوکال برای توسعه
- تصمیم و کلون عملی Materio Free روی ماشین توسعه
- مصوب مهاجرت کامل به Materio Free و حذف وابستگی UI قبلی

## در حال انجام
- پاک‌سازی منو/بنر Pro از Materio Free
- انتقال frontend به ساختار `frontend/` و اتصال به Laravel
- حذف پکیج‌های Inertia/Breeze UI/shadcn از مسیر اصلی

## بعدی
- Sanctum/SPA auth
- منوی دامنه هم‌ره (بدون آیتم تبلیغ Pro)
- M00 foundation روی API + صفحات Materio
- bottom navigation موبایل در صورت نیاز محصول

## ریسک
- ادغام ناتمام = دو استک هم‌زمان؛ باید طبق `12_MATERIO_STACK_MIGRATION.md` یکی شود.
- کپی تصادفی دارایی Pro ممنوع.
