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
| Audit | `AuditEvent.php`، `AuditObserver.php` و `AppServiceProvider.php` | Observer فقط روی Activity/Order/Quote/Task/WorkPackage ثبت شده و فهرست فیلدهای مجاز دارد؛ دامنه ثبت هنوز همه اسناد مالی/موجودی/هویت/شرکت را پوشش نمی‌دهد |
| Number Sequence | `app/Support/NumberSequence.php`، Migration و `NumberSequenceTest.php` | تراکنش، insertOrIgnore، lockForUpdate، unique company/key/period، کنترل Company Context و rollback در کد/تست مشاهده شد؛ تست هم‌زمانی واقعی هنوز اجرا/اثبات نشده است |
| Job Company Context | `CompanyAwareJobTest.php` | تست منبع برای Company ID صریح، عدم اتکا به Session و restore context وجود دارد |
| Attachment isolation | `AttachmentIsolationTest.php` | تست منبع برای private disk، جلوگیری از دانلود cross-company و حذف منطقی وجود دارد |

**محدودیت:** فایل‌های تست از Git خوانده شده‌اند، اما در این مرحله اجرا نشده‌اند. بنابراین نتیجه آزمون «نامشخص/اجرا نشده» است. وجود کلاس یا تست به معنی کامل بودن امنیت یا آمادگی انتقال نیست.

## ممیزی تکمیلی کد مشترک

### Company Context و Scope
- `CompanyContext` در `AppServiceProvider` به‌صورت scoped ثبت شده است؛ این برای جلوگیری از ماندگاری زمینه بین چرخه‌های درخواست/Job مناسب است.
- `BelongsToCompany` در زمان save، زمینه شرکت را اجباری و تغییر company_id را رد می‌کند؛ Global Scope در نبود زمینه، Query را خالی می‌کند.
- `CompanyContext::runForCompany()` فقط در Console اجرا می‌شود، شرکت فعال را بررسی می‌کند و زمینه سیستمی قبلی را در finally restore می‌کند.
- در Console، اگر زمینه سیستمی وجود نداشته باشد، `currentCompanyId()` هنوز می‌تواند از Session خوانده شود؛ Seederهای قدیمی نیز Session را برای هر شرکت تغییر می‌دهند. در معماری جدید Job/Command باید فقط از context صریح استفاده کند و fallback به Session حذف شود.
- در وب، AppServiceProvider انتخاب Session را با عضویت/فعال بودن شرکت بررسی و در نبود انتخاب، default صریح را تنظیم می‌کند. رفتار endpoint/API مستقل از Session هنوز باید طراحی شود.

### Job
- `CompanyAwareJob` شناسه شرکت را در constructor اجباری می‌کند، afterCommit، retry/backoff/timeout دارد و Middleware زمینه را resolve می‌کند.
- تست‌های خوانده‌شده زمینه صریح مستقل از Session، restore پس از exception، رد شرکت غیرفعال و سیاست retry را پوشش می‌دهند.
- این قرارداد فقط برای Jobهای توسعه‌یافته از کلاس پایه اعمال می‌شود؛ Jobهای دیگر خودکار company-aware نیستند. ممیزی همه Jobها و تضمین actor/authorization در Job باقی است.

### Number Sequence
- سرویس در Transaction ردیف sequence را با insertOrIgnore ایجاد و با lockForUpdate قفل می‌کند؛ unique(company_id, sequence_key, period) و کنترل company context دارد.
- تست‌های موجود افزایش ترتیبی، جداسازی شرکت/نوع سند، rollback و رد درخواست برای شرکت دیگر را پوشش می‌دهند.
- تست concurrency چند اتصال/درخواست واقعی در این فایل وجود ندارد؛ قفل و unique طراحی مناسبی دارند اما نتیجه هم‌زمانی در محیط MariaDB باید اجرا و اثبات شود.
- دوره پیش‌فرض با سال میلادی `now()->format('Y')` ساخته می‌شود؛ شماره سند نمایشی/شمسی و دوره مالی باید در قرارداد شماره‌گذاری جداگانه تصمیم‌گیری شود.

### Attachment
- StoreAttachment حجم را تا ۲۵ مگابایت کنترل، company_id والد را با شرکت جاری تطبیق و Gate update را اجرا می‌کند؛ فایل در مسیر تصادفی UUID ذخیره می‌شود.
- در صورت شکست ثبت Metadata، فایل فیزیکی حذف می‌شود؛ حذف عادی فقط SoftDelete است و فایل برای retention باقی می‌ماند.
- دانلود/Preview/Delete زیر auth هستند و Policy به Policy موجودیت والد ارجاع می‌دهد؛ Preview فقط MIME تصویری allowlist شده را با nosniff و private/no-store برمی‌گرداند.
- فایل‌ها در Migration به‌صورت Morph متصل‌اند؛ FK مرکب company_id + attachable وجود ندارد. مسیر Action این تعلق را کنترل می‌کند، اما ایجاد مستقیم Model می‌تواند آن را دور بزند؛ مسیرهای نوشتن باید به Action محدود شوند.
- purge فیزیکی زمان‌بندی‌شده، retention، quota، malware scan و رفتار حذف/بایگانی شرکت در شواهد بررسی‌شده مشاهده نشدند.

### Audit
- Observer فقط روی Activity، Order، Quote، Task و WorkPackage ثبت شده است؛ Invoice، Payment، StockLevel، StockMovement، SalesRepresentative، Company و تغییرات هویت مالی در ثبت Observer حاضر نیستند.
- فهرست فیلدهای Audit allowlist شده و notes آزاد عمداً ثبت نمی‌شوند؛ این تصمیم از افشای متن خصوصی جلوگیری می‌کند، اما برای هر سند باید مشخص شود کدام داده مالی/امنیتی ضروری است.
- actor از Auth::id خوانده می‌شود؛ در Job/CLI بدون کاربر تعاملی ممکن است null باشد. عملیات سیستمی باید actor نوع system/service و منبع/شناسه Job را ثبت کند.
- Observer در تراکنش فراخواننده شرکت می‌کند اگر save در transaction انجام شده باشد؛ برای عملیات خارج از transaction تضمین اتمیک بودن تغییر و Audit باید صریح شود.
- AuditEvent خودش company-scoped است؛ FK مرکب برای هم‌شرکتی بودن auditable و AuditEvent در Migration بررسی‌شده وجود ندارد.

### وضعیت تست
- فایل‌های `CompanyAwareJobTest`، `NumberSequenceTest`، `AttachmentIsolationTest`، `AttachmentPreviewTest` و `BusinessAuditTest` خوانده شدند.
- هیچ‌کدام در این ممیزی اجرا نشده‌اند؛ وجود تست، نتیجه اجرای موفق نیست.

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
