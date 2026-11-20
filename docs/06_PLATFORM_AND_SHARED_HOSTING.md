# زیرساخت و استقرار روی هاست اشتراکی

هدف، اجرای نسخه نهایی روی هاست اشتراکی متعارف است؛ معماری نباید به Docker، process manager اختصاصی، Node server یا daemon دائمی وابسته باشد.

## مبنای فعلی
Laravel + MariaDB در backend و Vue 3 در frontend. نسخه دقیق PHP/Laravel/DB پس از بررسی محیط واقعی و محدودیت هاست تعیین می‌شود. Node/Vite فقط برای توسعه و build است؛ فایل‌های build شده روی هاست قرار می‌گیرند.

## موارد بررسی پیش از تثبیت
- نسخه PHP و extensionها، memory_limit و execution time
- SSH و Composer/Artisan
- document root و امکان قراردادن public به‌عنوان web root
- MariaDB/MySQL، transaction، JSON و index
- cron و scheduler
- queue worker دائمی یا اجرای زمان‌بندی‌شده
- Redis availability؛ وابستگی به Redis بدون تأیید ممنوع
- storage، symlink، permission و upload limits
- SSL، backup، log و روش deploy/rollback

## اصول deploy
Secrets خارج Git؛ `.env` commit نشود. فقط public در دسترس وب باشد. Node runtime لازم نیست. build و deploy تکرارپذیر و rollback‌پذیر باشند. Migration با backup و بررسی سازگاری اجرا شود. health check، log و backup از ابتدا لحاظ شوند.

هیچ نسخه یا قابلیت هاست خاصی تا زمان دریافت مشخصات واقعی قطعی فرض نمی‌شود.
