# وضعیت پروژه

**تاریخ بازنگری:** 2026-11-21  
**مخزن:** `akhlaghimhd/hamarehOffice` | **شاخه:** `main`

## وضعیت مخزن جدید
- مخزن و شاخه main ایجاد شده‌اند.
- پوشه‌های سطح اول `docs/` و `project/` انگلیسی‌اند.
- Commit تغییر نام پوشه‌ها: `31a53aa45c8b171b228c78af19ea8052d97d9ea0`.
- پروژه جدید هنوز نصب/scaffold اجرایی Laravel نیست.
- نصب محلی، build، runtime test یا deploy جدید انجام نشده است.

## تصمیم‌های جاری
- Vue 3 برای UI؛ Filament در پروژه جدید نصب یا استفاده نمی‌شود.
- Laravel backend و MariaDB.
- هاست اشتراکی مقصد؛ Node فقط build-time.
- Inertia یا Blade + Vue/API هنوز باز است و باید با spike محدود تعیین شود.
- Identity واحد و جداسازی با `company_id` الزامی است.
- UI فارسی RTL، اعداد فارسی و تاریخ شمسی.
- مرحله فعلی: انتقال اسناد، بازنگری دانش و ممیزی پیاده‌سازی قدیمی.

## انتقال اسناد
- ۳۸ فایل Markdown از `hamarehCRM/اسناد/` در Commit `25ba93d0408b4b641e7fb52989e89557381cbaed` به `docs/reference/legacy-hamarehCRM/` منتقل شده‌اند.
- تطبیق Git Blob SHA برای هر ۳۸ فایل انجام شد؛ همه فایل‌ها با محتوای منبع یکسان‌اند.
- اسناد جاری پروژه جدید در سطح `docs/` نوشته شده‌اند؛ انتقال فایل به معنی پذیرش تصمیم‌های قدیمی نیست.
- دستورالعمل پیشنهادی Project ChatGPT در `docs/CHATGPT_PROJECT_INSTRUCTIONS.md` آماده است. جایگزینی تنظیمات Project Instructions باید توسط مالک در تنظیمات پروژه انجام شود.

## موجودی کد قدیمی
درخت Git مخزن قدیمی در Commit فوق شامل ۶۳ فایل Migration، ۵۰ فایل Model، ۲۴ فایل Action، ۳ فایل Service، ۳ فایل Policy، ۴۷ فایل تست و ۱۴۷ فایل در مسیر Filament Resources/Pages/Relations است. این اعداد شمارش فایل‌اند، نه شمارش قابلیت فعال یا نتیجه تست. جزئیات و محدودیت‌ها در `09_LEGACY_CODE_INVENTORY.md` آمده است.

## بازنگری ماژول‌ها
- M00 Foundation: سند `modules/M00_FOUNDATION.md` ایجاد شد؛ شواهد منتخب Company Scope، Attachment، Audit و تست‌های Sequence/Job/Attachment بررسی شده‌اند؛ تست‌ها اجرا نشده‌اند.
- M01 Identity & Access: سند `modules/M01_IDENTITY_ACCESS.md` ایجاد شد؛ مدل‌های User/Role/Permission، CompanyContext، Actionهای کلیدی عضویت/نقش/ایجاد شرکت و تست‌های منتخب بررسی شدند.
- M01 شکاف‌های طراحی شامل اتصال مستقیم User به Filament، دوگانگی pivot نقش legacy و جدید، رفتار دسترسی فراگیر نقش admin و نیاز به قرارداد یکپارچه مجوز و زمینه شرکت را ثبت کرده است.
- M02 Organization & Company: سند `modules/M02_ORGANIZATION_COMPANY.md` ایجاد شد؛ Company، تنظیمات نوع‌دار و پوسته، هویت مالی رسمی/غیررسمی، حساب‌های دریافت، Actionها، Migrationها و تست‌های منتخب بررسی شدند.
- M02 تصمیم‌های متأخر تاریخی درباره داشبورد مستقل شرکت و تعیین مدیر پس از ایجاد را از تصمیم‌های قدیمی‌تر Drawer/ایجاد اجباری مدیر اولیه تفکیک کرده است.
- M02 ریسک‌های یکپارچگی DB برای تطابق شرکت و هویت مالی حساب بانکی، یکتایی حساب پیش‌فرض فعال، و سیاست رمزگذاری/ماسک داده بانکی را ثبت کرده است.
- گزارش CI تاریخی در سند منبع M02 وجود دارد؛ تست‌ها در این بازنگری اجرا نشده‌اند و آن گزارش معادل تست پروژه جدید نیست.
- M03 Party & Customer: سند `modules/M03_PARTY_CUSTOMER.md` ایجاد شد؛ مدل Customer/CustomerContact، دو Migration، CustomerResource، ContactsRelationManager و تست‌های مرتبط ایزوله‌سازی منتخب بررسی شدند. کد قدیمی CRUD مشتری و تماس‌های چندگانه دارد، اما Party مشترک، نقش‌های چندگانه تجاری، Address نوع‌بندی‌شده و مالک حساب CRM در شواهد بررسی‌شده وجود ندارند. تست‌ها اجرا نشده‌اند.
- M04 Marketing & CRM: سند `modules/M04_MARKETING_CRM.md` ایجاد شد؛ مدل‌های Opportunity/Activity/Stage/Step، Service ثبت سریع، Policy فعالیت، Resource و میزکار فرصت، صفحه قیف، Migration گام پیگیری و تست ایزوله‌سازی بررسی شدند. کد قدیمی قابلیت‌های CRM متعددی دارد؛ بااین‌حال Transition و تاریخچه مرحله کامل اثبات نشده، قیف همه فرصت‌ها را در حافظه تجمیع می‌کند و مرز Step/Activity و ارجاعات هم‌شرکتی نیازمند تکمیل است. تست‌ها اجرا نشده‌اند.
- M05 Catalog & Product Configuration: سند `modules/M05_CATALOG_PRODUCT.md` ایجاد شد؛ اسناد معماری کاتالوگ، مدل‌های Line/Family/Season/Model/Option/Rule، Configurator، Migrationهای منتخب، Seederها، Resourceهای منتخب و تست Configurator بررسی شدند. کد قدیمی زیرساخت کاتالوگ نوتوپیا و ماتریس مجاز دارد، اما ProductType/Attribute عمومی، ارزیابی Rule، قیمت‌گذاری تفکیکی و Revision immutable کامل نیستند؛ Configurator حتی ProductType را با allowlist ثابت محدود می‌کند. تست‌ها اجرا نشده‌اند.
- M06 Sales, Quote & Order: سند `modules/M06_SALES_QUOTE_ORDER.md` ایجاد شد؛ مدل‌های Quote/QuoteLine/Order/OrderLine/WorkPackage، تبدیل اتمیک، سرویس موجودی، Migration اصلی و تست‌های تبدیل/وضعیت/رزرو/تحویل/بسته کاری بررسی شدند. تبدیل اتمیک و idempotent و کپی Snapshot شواهد و تست دارند؛ اما تبدیل Quote در کد منتخب الزام accepted/اعتبار را enforce نمی‌کند، Quote Revision و Change Order مشاهده نشدند و قفل دامنه‌ای OrderLine/Workflow نسخه‌دار کامل اثبات نشده است. تست‌ها اجرا نشده‌اند.
- M07 Production & Outsource Hub: سند `modules/M07_OUTSOURCE_PRODUCTION.md` ایجاد شد؛ WorkPackage، Migration، Seeder مجوز، Resourceها و تست چرخه بررسی شدند. کد قدیمی WorkPackage با نوع اجرای داخلی/برون‌سپاری/ترکیبی و چرخه وضعیت پایه دارد؛ اما Routing/Revision، ProductionRun، پیمانکار Partyمحور، تحویل مقداری و اتصال رویدادی به M10 در درخت بررسی‌شده مشاهده نشدند. تست‌ها اجرا نشده‌اند.
- M08 Workspace & Tasks: سند `modules/M08_WORKSPACE_TASKS.md` ایجاد شد؛ Task/Activity، SaveTask، Policy، میزکار، Migration، Seeder مجوز، پیوست و تست‌های منتخب بررسی شدند. Task پایه، ارجاع درون‌شرکتی، مشاهده مدیر مستقیم/غیرمستقیم، Audit و پیوست خصوصی شواهد و تست دارند؛ اما اعتبارسنجی مالکیت/نوع والد Morph در SaveTask مشاهده نشد، تغییر وضعیت مسیر Action مستقل ندارد و idempotency هم‌زمان بازیابی duplicate-key را نشان نمی‌دهد. تست‌ها اجرا نشده‌اند.
- M09 Operational Finance: سند `modules/M09_OPERATIONAL_FINANCE.md` ایجاد شد؛ Invoice/Payment، Migration، FinanceSeeder، Resourceها، اتصال مشتری، هویت مالی و تست‌های منتخب بررسی شدند. صدور از Order، Snapshot هویت، پرداخت جزئی/کامل و مانده مشتری شواهد و تست دارند؛ اما ثبت پرداخت در برابر race condition قفل Invoice ندارد، محاسبات float هستند، تخصیص یک پرداخت به چند فاکتور وجود ندارد، Policy مستقل مالی اثبات نشده و انتخاب صریح حساب دریافت در کد اجباری نیست. تست‌ها اجرا نشده‌اند.
- M10 Inventory Light: سند `modules/M10_INVENTORY.md` ایجاد شد؛ StockLevel/StockMovement، عملیات ورود/رزرو/آزادسازی/انتقال/مصرف/آماده/تحویل/اصلاح، OrderInventoryService، Migration، Resource و تست‌های منتخب بررسی شدند. قفل تراکنشی ردیف مانده و رزرو جلد/تحویل جزئی شواهد دارند؛ اما چند منبع موجودی برای جلد، نبود Projection قابل rebuild، نبود اتصال رزرو به انتقال تولید، پوشش ناقص داخله/ضایعات و idempotency/concurrency از شکاف‌ها هستند. تست‌ها اجرا نشده‌اند.
- M11 Reporting & Notifications: سند `modules/M11_REPORTING_NOTIFICATIONS.md` ایجاد شد؛ KPI Widgetها، FollowUpNeeded، Command اعلان سررسید، Scheduler، Migration notifications و تست منتخب بررسی شدند. KPI پایه و اعلان ساعتی Task/Activity شواهد دارند؛ اما dedupe فقط Cacheمحور است، ترجیحات کانال/Escalation/Export کامل نیست، URLها Filament-specific هستند و تست Activity/dedupe/company/timezone محدود است. تست اجرا نشده است.
- M12 Sales Network & Commission: سند `modules/M12_SALES_NETWORK_COMMISSION.md` ایجاد شد؛ SalesRepresentative، Migration، Seeder مجوز، Resource، اتصال Opportunity/Quote/Order، Snapshot سفارش و تست منتخب بررسی شدند. اسکلت نماینده داخلی/بیرونی و Snapshot تک‌نماینده وجود دارد؛ اما اتصال نماینده بیرونی به Party، قرارداد نسخه‌دار، چند User محدود، انتساب چندذی‌نفع، CommissionPlan/Entry/Adjustment/Settlement و دسترسی بیرونی در کد منتخب وجود ندارد. نرخ و مبنای استحقاق عمداً تصمیم‌گیری‌نشده باقی مانده‌اند و بدون تأیید مالک فعال نمی‌شوند. تست اجرا نشده است.

## کارهای باز
1. تکمیل ممیزی عمیق M00 تا M02 شامل Migrationها، Seederها، Middlewareها، Routeها، Policyها، Jobها و یکپارچگی DB؛ اسناد فعلی این سه ماژول بررسی منتخب‌اند.
2. ممیزی اتصال بین‌ماژولی M00 تا M12: Company Context/Identity، Party، Catalog Revision، Quote/Order Snapshot، Workflow/Production، Inventory Ledger، Invoice/Payment و Commission.
3. تکمیل ماتریس سراسری «نیازمندی/تصمیم/کد قدیمی/مجوز/تست/شکاف/قرارداد جدید» و ثبت تصمیم‌های نیازمند مالک.
4. بررسی prototype Vue به‌عنوان مرجع آزمایشی، نه مبنای معماری قطعی.
5. دریافت مشخصات واقعی هاست اشتراکی و اجرای spike محدود انتخاب Inertia یا Blade + Vue/API با معیارهای auth/session، build، deploy، cron/queue و rollback.
6. تعیین نسخه دقیق PHP/Laravel/MariaDB/Node بر اساس محیط محلی و هاست مقصد.
7. پس از تکمیل ممیزی و تثبیت معماری، نصب محلی و vertical slice؛ تا آن زمان scaffold انجام نشود.

ممنوعیت مرحله جاری:** نصب Laravel، scaffold و نصب پکیج‌ها تا پایان بازنگری اسناد و ممیزی دانش شروع نشود.

«مستند» به معنی پیاده‌سازی نیست. «پیاده‌سازی قدیمی» به معنی انتقال به جدید نیست. «تست موجود» به معنی اجرای موفق فعلی نیست.
