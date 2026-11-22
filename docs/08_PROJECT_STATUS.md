# وضعیت پروژه

**به‌روزرسانی:** 2026-11-22

## برند
- نام کوتاه: **هماره**
- نام کامل: **هوشمند آرمان هماره**

## استک مصوب UI
- **Materio Free + Vue 3 + Vuetify 3 + Vue Router**
- Laravel backend/API + Sanctum token
- Filament / Inertia / shadcn به‌عنوان سیستم اصلی ممنوع

## انجام‌شده
- مخزن، اسناد، Docker MariaDB
- Materio Free در `frontend/` + حذف Pro teaser
- Sanctum login/logout/user
- RTL پایه + فونت محلی Vazir
- برندینگ پوسته (هماره)
- **M00:** companies, number_sequences, attachments, audit_events, company_settings + مدل‌ها + FoundationSeeder
- **M01 (اسکلت):** company_user, roles, permissions, company_user_role, platform_owners
- CompanyContext + middleware `company.context`
- API: `/api/companies`, `/api/companies/switch`؛ login شامل لیست شرکت‌ها

## بعدی
- نمایش انتخاب شرکت در UI Materio
- Permission resolver واحد (بدون wildcard خام admin)
- M02 تکمیل فیلدهای شرکت / تنظیمات
- M03 Party/Customer به‌عنوان اولین vertical slice دامنه قابل‌مشاهده

## ریسک
- نقش admin هنوز policy کامل ندارد؛ قبل از صفحات عملیاتی باید Enforce سرور تکمیل شود.
