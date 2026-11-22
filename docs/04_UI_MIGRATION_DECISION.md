# تصمیم مهاجرت رابط کاربری

**وضعیت:** مصوب — بازنگری 2026-11-22  
**مخزن:** `akhlaghimhd/hamarehOffice` | **شاخه:** `main`

## تصمیم مصوب (جاری)

| لایه | انتخاب |
|------|--------|
| Backend | Laravel (API + دامنه + مجوز) |
| Frontend | **Vue 3 + Vue Router + Vuetify 3** |
| قالب پایه UI | **Materio Free** (MIT، ThemeSelection) |
| استایل | Vuetify theme + SCSS قالب |
| احراز هویت UI | Sanctum (SPA stateful) یا قرارداد معادل session/CSRF |
| Database | MariaDB |
| استقرار | هاست اشتراکی؛ Node فقط build-time |

### صریحاً کنار گذاشته شد (نباید دوباره نصب شوند)
- Filament
- Inertia
- shadcn-vue / Radix-vue به‌عنوان سیستم اصلی
- Tailwind به‌عنوان سیستم اصلی UI (ممکن است tooling جزئی بماند ولی مبنا Vuetify است)
- Breeze scaffolding به‌عنوان پوسته UI نهایی

## معیارهای مالک
1. آزادی UI و موبایل
2. ظاهر مدرن بدون نوشتن همه چیز از صفر
3. انعطاف سناریو بدون قوانین خشک پنل‌ساز
4. موبایل + دسکتاپ؛ حس شبه اپ (از جمله امکان bottom nav موبایل)
5. هاست اشتراکی بدون Node runtime دائمی
6. محدودیت مالی: فقط مسیر **رایگان (MIT)** — Pro خریداری نمی‌شود

## Materio Free در برابر Pro
- Free برای همیشه MIT است؛ قفل زمانی ندارد.
- برچسب‌های Pro و دکمه Upgrade در سورس Free **تبلیغ‌اند**؛ باید از منو و Layout **حذف** شوند تا در محصول نهایی دیده نشوند.
- صفحات و دارایی اختصاصی Pro داخل پکیج Free نیستند و کپی غیرقانونی ممنوع است.
- امکانات شبیه Pro در صورت نیاز **با کد خود پروژه** روی Free ساخته می‌شوند.

## معماری اجرایی
- Laravel: مدل، مایگریشن، Policy، Action، API، چندشرکتی `company_id`.
- Vue/Materio: فقط presentation، navigation، state UI.
- بدون منطق حیاتی کسب‌وکار فقط در Vue.
- یک مخزن؛ frontend داخل همین پروژه (مثلاً `frontend/` یا جایگزینی `resources` طبق راهنمای مهاجرت).
- خروجی `npm run build` فایل استاتیک قابل سرو از `public`.

## ممنوعیت
- نصب Filament
- بازگرداندن Inertia/shadcn به‌عنوان استک اصلی بدون تصمیم جدید کتبی
- نگه داشتن بنر/منوی Upgrade to Pro در محصول
- Node runtime دائمی روی هاست
- کپی سورس یا دارایی Materio Pro بدون لایسنس

## ارجاع
- استاندارد UX: `05_UI_UX_STANDARDS.md`
- هاست: `06_PLATFORM_AND_SHARED_HOSTING.md`
- راهنمای اجرایی مهاجرت: `12_MATERIO_STACK_MIGRATION.md`
- وضعیت: `08_PROJECT_STATUS.md`
