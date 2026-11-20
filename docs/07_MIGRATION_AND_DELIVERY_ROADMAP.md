# نقشه راه انتقال دانش و تحویل

## فاز ۰ — اسناد (فعال)
انتقال همه اسناد قدیمی به `docs/reference/legacy-hamarehCRM/`؛ بازنویسی اسناد جاری مطابق Vue 3 و هاست اشتراکی؛ حذف ارجاعات جاری به Filament از اسناد جدید؛ تفکیک تصمیم/نیازمندی از کد و تست؛ تکمیل دستورالعمل ChatGPT. در این فاز نصب یا scaffold انجام نمی‌شود.

## فاز ۱ — ممیزی دانش و کد قدیمی
برای هر ماژول سند قدیمی، مدل‌ها، migrationها، Action/Serviceها، policyها، jobها، eventها، controllerها و تست‌ها بررسی شوند. سناریوهای موفق، خطا، مرزی، مجوز و جداسازی شرکت استخراج شوند. وضعیت قابلیت با مسیر کد و نام تست ثبت شود.

## فاز ۲ — تثبیت معماری اجرایی
محدودیت هاست بررسی؛ Inertia در برابر Blade/Vue/API با spike تصمیم‌گیری؛ قرارداد auth/session/company context/permission و روش تاریخ، عدد، فایل، queue، scheduler و deploy تثبیت شوند.

## فاز ۳ — محیط محلی
پس از تکمیل اسناد و معماری، نسخه‌های Linux/Docker/PHP/Composer/Node/MariaDB بررسی؛ سپس scaffold و نصب کنترل‌شده، env و smoke test انجام شود. این فاز هنوز آغاز نشده است.

## فاز ۴ — vertical slice پایه
ورود واحد، انتخاب شرکت، اثبات company_id، policy/permission، layout فارسی RTL، اعداد و تاریخ، یک جریان نمونه و deploy آزمایشی روی محیط هم‌ارز هاست.

## فاز ۵ — توسعه ماژول‌ها
Foundation → Identity/Company → Party/CRM → Catalog → Quote/Order → Outsource Production → Tasks → Inventory → Operational Finance → Reporting/Notifications؛ اولویت نهایی با مالک و شواهد ممیزی.

## فاز ۶ — پذیرش
تست واحد/Feature/امنیت/شرکت، UI در دستگاه‌ها، deploy، backup/restore، rollback و پذیرش مالک.

## دروازه‌ها
تا پایان بازنگری اسناد و ممیزی اولیه، نصب شروع نشود. تا روشن‌شدن هاست، وابستگی دائمی به Redis/worker/Node فرض نشود. تا اثبات Company Context، صفحات عملیاتی توسعه نیابند.
