# اسناد ماژول‌های هم‌ره

این اسناد نسخه بازنگری‌شده قراردادهای دامنه‌اند. برای هر ماژول، نیازمندی، شواهد مخزن قدیمی، وضعیت تست، قرارداد پروژه جدید، سناریوهای پذیرش و کارهای باز جدا ثبت می‌شود.

## وضعیت
- [M00 — Foundation](M00_FOUNDATION.md): بازنگری اولیه انجام شده؛ تست‌ها در این مرحله اجرا نشده‌اند.
- [M01 — Identity & Access](M01_IDENTITY_ACCESS.md): نیازمندی، شواهد منتخب کد و تست‌ها بررسی شد؛ ممیزی کامل و اجرای تست‌ها باقی است.
- [M02 — Organization & Company](M02_ORGANIZATION_COMPANY.md): نیازمندی و تصمیم‌های تاریخی، مدل‌ها، Actionها، تنظیمات، هویت مالی، Migrationها و تست‌های منتخب بررسی شد؛ ممیزی کامل و اجرای مستقل تست‌ها باقی است.
- [M03 — Party & Customer](M03_PARTY_CUSTOMER.md): سند بازنگری ایجاد شد؛ نیازمندی تاریخی، مدل‌ها، Migrationها، Resource و تست ایزوله‌سازی منتخب بررسی شدند؛ تست‌ها اجرا نشده‌اند و مدل مشترک Party در کد قدیمی مشاهده نشد.\n- [M04 — Marketing & CRM](M04_MARKETING_CRM.md): سند بازنگری ایجاد شد؛ Opportunity، Activity، Stage، Step، ثبت سریع، Policy، میزکار، قیف، Migration منتخب و تست ایزوله‌سازی بررسی شدند؛ تست‌ها اجرا نشده‌اند و Transition کامل/بهینه‌سازی قیف نیازمند کار است.\n- [M05 — Catalog & Product Configuration](M05_CATALOG_PRODUCT.md): سند بازنگری ایجاد شد؛ مدل‌های کاتالوگ، Configurator، Rule، Migrationها، Seederها و تست منتخب بررسی شدند؛ Rule Engine، ProductType/Attribute عمومی، قیمت‌گذاری کامل و Revision immutable در شواهد کامل نیستند؛ تست‌ها اجرا نشده‌اند.\n- [M06 — Sales, Quote & Order](M06_SALES_QUOTE_ORDER.md): سند بازنگری ایجاد شد؛ مدل‌های Quote/Order/Line/WorkPackage، تبدیل تراکنشی، سرویس موجودی، Migration و تست‌های منتخب بررسی شدند؛ Revision Quote، Change Order، قفل دامنه‌ای خطوط قطعی و Workflow نسخه‌دار نیازمند تکمیل‌اند؛ تست‌ها اجرا نشده‌اند.\n- [M07 — Production & Outsource Hub](M07_OUTSOURCE_PRODUCTION.md): سند بازنگری ایجاد شد؛ WorkPackage، Migration، Seeder، Resource و تست چرخه بررسی شدند؛ Routing/Revision، ProductionRun، پیمانکار Partyمحور، تحویل مقداری و اتصال رویدادی M10 در کد منتخب مشاهده نشدند؛ تست‌ها اجرا نشده‌اند.\n- M08 تا M12: در صف بازنگری؛ اسناد مرجع تاریخی در `../reference/legacy-hamarehCRM/system/modules/` هستند.

## قاعده
هیچ ماژولی صرفاً بر اساس سند قدیمی «پیاده‌سازی‌شده» تلقی نمی‌شود. انتقال به پروژه جدید پس از بررسی کد، Migration، مجوز و تست و با حفظ منطق کسب‌وکار انجام می‌شود.
