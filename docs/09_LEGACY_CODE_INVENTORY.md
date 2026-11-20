# فهرست شواهد کد مخزن قدیمی

**تاریخ بررسی:** 2026-11-20  
**مخزن:** `akhlaghimhd/hamarehCRM`  
**شاخه فقط‌خواندنی:** `feature/filament5-upgrade`  
**Commit بررسی‌شده:** `25ba93d0408b4b641e7fb52989e89557381cbaed`

این سند فقط موجودی فایل‌های Git در Commit فوق را ثبت می‌کند. شمارش فایل، اثبات صحت منطق یا موفقیت تست نیست.

## موجودی ساختاری

| دسته | تعداد فایل در درخت Git | تفسیر |
|---|---:|---|
| Migration | 63 | تغییرات تاریخی Schema که باید به ترتیب و با داده واقعی بررسی شوند |
| Model | 50 | شامل مدل‌های دامنه و فایل‌های پشتیبان؛ شمارش فایل، شمارش مدل فعال نیست |
| Action | 24 | منطق عملیاتی متمرکز در بخشی از دامنه |
| Service | 3 | سرویس‌های CatalogConfigurator، OpportunityQuickCreate و OrderInventoryService |
| Policy | 3 | ActivityPolicy، AttachmentPolicy و TaskPolicy |
| Feature/Unit Test | 47 | وجود فایل تست؛ نتیجه اجرای فعلی از این شمارش قابل استنتاج نیست |
| Filament Resource/Page/Relation file | 147 | فایل‌های رابط قدیمی؛ شمارش فایل است، نه تعداد قابلیت مستقل |

## آنچه این فهرست نشان می‌دهد

- مخزن قدیمی صرفاً یک طرح مفهومی نیست؛ Migrationها، Modelها، Actionها، Serviceها و تست‌های متعددی دارد.
- پیاده‌سازی قدیمی به‌شدت به Filament متکی است؛ این لایه نباید بدون تحلیل قابلیت و مجوز به پروژه جدید منتقل شود.
- بخشی از منطق دامنه در Action/Service قرار گرفته و می‌تواند نامزد انتقال مفهومی باشد؛ انتقال کد مستقیم بدون بررسی وابستگی‌ها مجاز نیست.
- فایل‌های پشتیبان با پسوندهایی مانند `.bak` در درخت Git وجود دارند؛ نباید به‌عنوان کد فعال شمرده شوند.
- تست‌های موجود باید در محیط قابل تکرار اجرا شوند؛ نام تست یا وجود فایل، نتیجه سبز را ثابت نمی‌کند.

## گروه‌های تست قابل مشاهده در درخت

نمونه نام تست‌ها:
- CompanyIsolationTest، SwitchCompanyTest، CompanyAwareJobTest
- RoleResourceProtectionTest، UserRoleHierarchyTest
- CatalogConfiguratorTest
- QuoteOrderConversionTest، OrderStatusTransitionTest
- OrderInventoryReservationTest، OrderPartialDeliveryTest، StockLevelTest
- InvoicePaymentTest، InvoiceIsolationTest
- TaskIsolationTest، TaskTeamAccessTest
- WorkPackageTest، SalesRepresentativeTest
- NormalizePersianDigitsTest

این نام‌ها صرفاً مسیرهای شروع ممیزی‌اند. محتوا، پوشش واقعی، وابستگی‌ها و نتیجه اجرا باید جداگانه بررسی شوند.

## محدودیت این بررسی

- این بررسی، فهرست درخت Git در Commit مشخص است؛ اجرای برنامه یا تست در محیط قدیمی انجام نشده است.
- هیچ‌یک از قابلیت‌های مخزن قدیمی به‌معنی پیاده‌سازی‌شدن در hamarehOffice نیست.
- مسیرهای Filament فقط برای استخراج رفتار و سناریوهای موجود بررسی می‌شوند؛ UI جدید با Vue طراحی خواهد شد.
- قبل از انتقال هر Action/Service/Model، وابستگی به Facadeها، Auth، Session، Filament، Global Scope، Event و Migrationهای قبلی باید مشخص شود.

## گام بعدی ممیزی

برای هر ماژول M00 تا M12 یک ماتریس مستقل تکمیل شود: نیازمندی مستند، جدول/Migration، Model، Action/Service، Controller/Route یا UI قدیمی، Policy/Permission، تست مرتبط، نتیجه اجرای تست، شکاف و تصمیم انتقال. تا زمان تکمیل این ماتریس، وضعیت قابلیت «تأییدنشده برای پروژه جدید» است.
