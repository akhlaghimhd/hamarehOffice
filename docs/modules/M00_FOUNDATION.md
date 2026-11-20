# M00 — Foundation | زیرساخت مشترک

**منبع نیازمندی تاریخی:** `docs/reference/legacy-hamarehCRM/system/modules/M00_Foundation.md`  
**منبع کد بررسی‌شده:** `hamarehCRM` / `feature/filament5-upgrade` / `25ba93d0408b4b641e7fb52989e89557381cbaed`  
**وضعیت انتقال:** نیازمندی بازنگری شده؛ کد قدیمی فقط مرجع است؛ پیاده‌سازی در hamarehOffice آغاز نشده است.

## هدف
تأمین قابلیت‌های مشترک مورد نیاز ماژول‌ها: Company Context، پیوست امن، شماره‌گذاری اسناد، Audit کسب‌وکار و تنظیمات شرکت. این لایه باید مستقل از Vue و هر UI باشد.

## دامنه و رفتار مورد انتظار

### Company Context
- درخواست وب، Job، Command و عملیات سیستمی باید زمینه شرکت را به‌صورت صریح و معتبر دریافت کنند.
- عملیات شرکت‌محور بدون شناسه شرکت معتبر باید fail-closed باشد.
- Session فقط برای انتخاب شرکت در تعامل وب است؛ Job نباید به Session تکیه کند.
- Global Scope به‌تنهایی کافی نیست؛ مسیرهای بدون Scope، Relation، Policy، Export و فایل نیز باید محافظت شوند.

### Attachment
- فایل به موجودیت مجاز و همان شرکت متصل شود.
- metadata شامل company، disk/path، نام اصلی، MIME، اندازه، checksum، uploader و زمان باشد.
- دریافت فایل از Route کنترل‌شده و Policyدار انجام شود؛ مسیر عمومی یا قابل حدس ممنوع.
- حذف منطقی و نگهداری/پاک‌سازی فیزیکی سیاست جدا داشته باشند.

### Number Sequence
- شماره‌گذاری در محدوده شرکت و نوع سند مستقل باشد.
- صدور شماره اتمیک و تراکنشی باشد؛ درخواست همزمان شماره تکراری نسازد.
- rollback تراکنش، افزایش Sequence را نیز rollback کند.

### Audit
- Audit کسب‌وکار از Log فنی جدا باشد.
- actor، company، subject، event، before/after، metadata و زمان قابل ردیابی باشند.
- رخدادهای حساس و تغییرات هویت/شرکت/نقش/سند مالی/موجودی باید ثبت شوند.

### Company Settings
- تنظیمات دارای key، نوع داده، scope و validation باشند.
- داده حساس در صورت نیاز رمزگذاری شود.
- Cache و تنظیمات با تغییر شرکت از هم جدا بمانند.

## شواهد مشاهده‌شده در کد قدیمی

| قابلیت | شواهد Git | برداشت مجاز |
|---|---|---|
| Company scope | `app/Models/Concerns/BelongsToCompany.php` | در saving، شرکت جاری الزامی است؛ در نبود Company Context، Global Scope نتیجه خالی می‌دهد |
| Attachment | `Attachment.php`, `StoreAttachment.php`, `DeleteAttachment.php` | مدل polymorphic، checksum، uploader و ذخیره فایل؛ StoreAttachment مجوز update موجودیت والد را بررسی می‌کند |
| Audit | `AuditEvent.php` | مدل رخداد با actor، subject، before/after و metadata وجود دارد |
| Number Sequence | `NumberSequenceTest.php` و Migration مربوط | تست‌های منبع برای توالی، جداسازی شرکت/نوع سند و rollback وجود دارند؛ خود سرویس باید جداگانه ممیزی شود |
| Job Company Context | `CompanyAwareJobTest.php` | تست منبع برای Company ID صریح، عدم اتکا به Session و restore context وجود دارد |
| Attachment isolation | `AttachmentIsolationTest.php` | تست منبع برای private disk، جلوگیری از دانلود cross-company و حذف منطقی وجود دارد |

**محدودیت:** فایل‌های تست از Git خوانده شده‌اند، اما در این مرحله اجرا نشده‌اند. بنابراین نتیجه آزمون «نامشخص/اجرا نشده» است. وجود کلاس یا تست به معنی کامل بودن امنیت یا آمادگی انتقال نیست.

## قرارداد انتقال به پروژه جدید
- قرارداد Company Context در Laravel تعریف می‌شود؛ Vue فقط شرکت انتخاب‌شده و مجوزهای نمایشی را دریافت می‌کند.
- تمام endpointها و عملیات دامنه باید Company Context را مجدداً سمت سرور enforce کنند.
- فایل‌ها با storage خصوصی قابل پشتیبانی روی هاست هدف پیاده شوند؛ دسترسی symlink یا public storage نباید مفروض باشد.
- شماره‌گذاری باید با قابلیت‌های تراکنشی MariaDB و محدودیت‌های هاست سازگار باشد.
- Audit نباید به رویدادهای UI یا Filament وابسته باشد.

## سناریوهای پذیرش لازم
1. کاربر شرکت A نتواند رکورد، فایل، پیوست یا شماره شرکت B را مشاهده یا تغییر دهد.
2. درخواست عملیاتی بدون Company Context رد شود؛ نتیجه خالی تصادفی جایگزین خطای قابل ردیابی نشود.
3. Job با company_id صریح اجرا شود، مستقل از Session باشد و پس از موفقیت/خطا Context را پاک/restore کند.
4. دو درخواست همزمان برای یک شرکت و نوع سند شماره یکتا بگیرند.
5. rollback عملیات، شماره رزروشده و داده‌های وابسته را به وضعیت سازگار برگرداند.
6. فایل با Policy موجودیت والد ذخیره و دانلود شود؛ کاربر غیرمجاز و شرکت دیگر دسترسی نداشته باشند.
7. حذف منطقی فایل، سابقه Audit و سیاست نگهداری فایل فیزیکی را رعایت کند.
8. رخداد حساس، actor و before/after معتبر داشته باشد.

## کارهای باز
- بررسی کامل `CompanyContext`، `NumberSequence`، Middleware و Job middleware از کد منبع.
- بررسی Migrationها، unique/indexهای company-scoped و مسیرهای دانلود/حذف فایل.
- اجرای تست‌های مرتبط در محیط قابل تکرار و ثبت نتیجه واقعی.
- تعریف قرارداد مستقل از Filament برای Company Context، Attachment، Audit و Sequence در پروژه جدید.
- طراحی سیاست backup/retention فایل و Audit متناسب با هاست اشتراکی.

## وضعیت
- نیازمندی: استخراج و بازنویسی اولیه انجام شد.
- پیاده‌سازی در مخزن قدیمی: شواهد کد و تست موجود است؛ تکمیل نهایی تأیید نشده.
- تست در این بازنگری: اجرا نشده.
- پیاده‌سازی در hamarehOffice: انجام نشده.
