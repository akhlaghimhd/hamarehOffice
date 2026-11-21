# ممیزی اتصال بین‌ماژولی و معماری اجرایی | Cross-module Architecture Review

**مخزن:** `akhlaghimhd/hamarehOffice` / `main`  
**مبنای کد تاریخی:** `hamarehCRM` / `feature/filament5-upgrade` / `25ba93d0408b4b641e7fb52989e89557381cbaed`  
**نوع سند:** جمع‌بندی معماری اولیه بر پایه اسناد M00 تا M12؛ نه طراحی نهایی دیتابیس و نه مجوز شروع پیاده‌سازی.  
**وضعیت:** همه ۱۳ ماژول سند بازنگری دارند؛ M00 تا M02 ممیزی منتخب/اولیه‌اند و تمام تست‌های کد قدیمی در این دور اجرا نشده‌اند.

## 1. جمع‌بندی اجرایی

ماژول‌های قدیمی هر کدام بخش‌هایی از قابلیت عملیاتی را دارند، اما اتصال آن‌ها هنوز قرارداد واحد و قابل انتقالی نمی‌سازد. مهم‌ترین مسئله، انتخاب UI یا ساخت صفحات نیست؛ ابتدا باید مرزهای داده، هویت، شرکت، Snapshot، Workflow و رویدادهای مالی/موجودی یکپارچه شوند.

### موانع اصلی پیش از Scaffold عملیاتی

1. **Identity و Company Context:** قرارداد واحد مجوز، عضویت چندشرکتی و اجرای وب/API/Job/CLI باید پیش از هر endpoint عملیاتی تثبیت شود.
2. **Party Canonical Model:** Customer قدیمی در CRM، Quote/Order/Invoice و نماینده بیرونی استفاده شده؛ M03 باید Party مشترک و نقش‌های تجاری را مرجع کند.
3. **Versioned Catalog → Quote → Order:** M05 Revision و Snapshot کامل لازم دارد؛ M06 باید Revision دقیق و Snapshot را قطعی کند؛ M07 باید Routing همان نسخه را مصرف کند.
4. **Workflow و Production:** وضعیت‌های ثابت Model و WorkPackage کافی نیستند؛ Transition و تاریخچه و اجرای مسیر باید قابل نسخه‌بندی باشند.
5. **Inventory Ledger:** چند منبع موجودی قدیمی، نبود rebuild Projection، اتصال ناقص رزرو به انتقال تولید و پوشش ناقص جلد/داخله باید حل شود.
6. **Financial Correctness:** پرداخت هم‌زمان، محاسبه float، عدم تخصیص چندفاکتوری و نبود Policy مالی مستقل مانع اتکای مالی‌اند.
7. **Sales Attribution & Commission:** انتساب چندذی‌نفع و Snapshot قرارداد/سهم باید قبل از فعال‌سازی پورسانت طراحی شود؛ هیچ نرخ پیش‌فرضی تعیین نشده است.
8. **Shared Hosting Delivery:** انتخاب Inertia یا Blade + Vue/API و راهبرد Cron/Queue/Storage/Deploy باید با مشخصات واقعی هاست آزمایش شود.

## 2. قراردادهای مشترک که باید یک‌بار تعریف شوند

### 2.1 شرکت و زمینه اجرا — M00/M01/M02

- تمام رکوردهای عملیاتی با `company_id` محدود می‌شوند؛ `tenant_id` استفاده نمی‌شود.
- Company Context از عضویت و فعال بودن حساب/شرکت resolve می‌شود؛ ورودی کلاینت قابل اعتماد نیست.
- وب از Session معتبر برای انتخاب شرکت استفاده می‌کند؛ API باید شرکت را از عضویت/توکن/درخواست مجاز resolve کند؛ Job/Command باید company_id صریح داشته باشد.
- نبود زمینه شرکت باید fail-closed و قابل مشاهده در Log باشد؛ fallback به اولین شرکت ممنوع.
- تمام Query، Relation، Policy، Action، Export، Attachment، Notification و Job باید همان قرارداد را اجرا کنند.
- یک Permission Resolver و Policy/Authorization Contract واحد لازم است؛ مجوزهای Vue فقط برای نمایش هستند.
- مدیر شرکت و Platform Owner دو سطح متفاوت‌اند؛ رفتار admin فراگیر قدیمی بدون تصمیم صریح کپی نشود.
- نقش‌های چندگانه عضویت باید یک مدل داده واحد داشته باشند؛ dual pivot قدیمی تکرار نشود.

### 2.2 Party و هویت تجاری — M03/M04/M06/M09/M12

- Party مرجع واحد شخص/سازمان است؛ نقش‌هایی مانند customer، supplier، contractor و sales_representative به آن متصل می‌شوند.
- Contact و Address موجودیت/ارزش‌های مرتبط با Party هستند؛ تکرار نام/موبایل/نشانی در ماژول‌ها باید معنای Snapshot یا داده جاری روشن داشته باشد.
- Opportunity، Quote، Order و Invoice به Party و نقش معتبر در همان شرکت متصل می‌شوند؛ Customer قدیمی نباید در ماژول‌های جدید مرجع موازی باقی بماند.
- پیمانکار M07 و نماینده بیرونی M12 نقش‌های Party هستند؛ رشته آزاد contractor_name و Customer اختصاصی برای نماینده، قرارداد نهایی نیست.
- کاربر داخلی همچنان User/Company Membership است؛ Party نماینده داخلی نباید حساب/Role جدید ایجاد کند.
- هر FK به Party باید تعلق Party/Role به همان شرکت را enforce کند.

### 2.3 کاتالوگ، فروش و Snapshot — M05/M06

- ProductType، Attribute، Option، Compatibility Matrix، Rule و PriceList داده‌محور و نسخه‌دار هستند.
- تعریف منتشرشده immutable است؛ تغییر با Revision جدید انجام می‌شود.
- QuoteLine به Product Definition Revision مشخص وصل است و Selection/Price Breakdown را نگه می‌دارد.
- Confirm Quote به Order اتمیک و idempotent است و وضعیت accepted/validity را کنترل می‌کند.
- OrderLine Snapshot شامل Revision محصول، انتخاب‌ها، Ruleهای مؤثر، قیمت، ارز، تخفیف/مالیات و اطلاعات نمایشی لازم است.
- تغییر کاتالوگ، Party، قیمت یا نماینده نباید سند قطعی قبلی را بازنویسی کند.
- Change Order تنها مسیر اصلاح سفارش قطعی است؛ نسخه قبلی حفظ می‌شود.
- Invoice از Order Snapshot صادر می‌شود، نه قیمت جاری کاتالوگ.

### 2.4 Workflow، تولید و وظایف — M04/M06/M07/M08

- Workflow Definition/Version و Transition History یک قرارداد عمومی دارند؛ وضعیت‌های هر Model منبع مستقل و موازی نسازند.
- Opportunity، Quote، Order، ProductionRun، WorkPackage و Task هرکدام State Machine دامنه‌ای خود را دارند، اما از primitiveهای مشترک و Audit/actor/time استفاده می‌کنند.
- Task ابزار واگذاری انسانی است؛ Activity تعامل CRM است؛ ProductionStep/WorkPackage اجرای تولید است. این مفاهیم با یکدیگر ادغام نشوند.
- تکمیل Task به‌تنهایی وضعیت Order یا تولید را تغییر نمی‌دهد؛ Transition مجاز باید Action مستقل داشته باشد.
- RoutingTemplate/Revision از پیکربندی M05 انتخاب و هنگام قطعی شدن سفارش Snapshot می‌شود.
- WorkPackage به Party پیمانکار و Step/OrderLine معتبر متصل است؛ پیمانکار فقط داده واگذارشده را می‌بیند.
- تحویل/پذیرش/رد/دوباره‌کاری دارای مقدار، actor، زمان، علت و مستندات مستقل‌اند.

### 2.5 موجودی — M06/M07/M10

- StockItem عمومی است؛ CoverSku فقط نمونه است.
- Ledger immutable مرجع حقیقت و StockBalance Projection قابل rebuild است.
- Reservation به OrderLine و StockItem متصل است؛ آزادسازی/مصرف رزرو بر اساس مقدار باز همان خط انجام می‌شود.
- Transfer Reserved Stock به Production از رزرو کم و به WIP اضافه می‌کند؛ انتقال دوباره‌شمارش یا استفاده از موجودی آزاد نامرتبط ممنوع است.
- Consumption، Scrap، Sample، Return، Mark Ready و Delivery رویدادهای مستقل‌اند.
- جلد و داخله دو قلم/جریان جدا هستند؛ فرمول مصرف داخله تا تصمیم مالک hard-code نمی‌شود.
- تحویل تجاری M06 و تحویل فیزیکی M10 مرتبط‌اند اما یک رویداد واحد نیستند.

### 2.6 مالی و پورسانت — M09/M12

- M09 فاکتور/پرداخت عملیاتی است؛ دفتر کل حسابداری خارج از محدوده فعلی است.
- مبلغ با Decimal/integer دقیق محاسبه می‌شود؛ float و epsilon ممنوع.
- Payment و PaymentAllocation جدا هستند تا یک دریافت بتواند به چند Invoice تخصیص یابد.
- ثبت/تخصیص پرداخت با قفل اتمیک مانع overpayment هم‌زمان می‌شود.
- Void، Refund، Chargeback و Adjustment مفاهیم جدا و رویدادمحورند.
- Invoice Snapshot نسخه‌دار هویت صادرکننده و حساب دریافت را نگه می‌دارد؛ حساب باید طبق تصمیم ثبت‌شده در زمان صدور صریحاً انتخاب شود.
- CommissionPlan/Rule/Entry/Adjustment/Settlement نسخه‌دار و idempotent هستند؛ تسویه از M09 انجام می‌شود.
- نرخ، مبنای استحقاق، تقسیم سهم، دوره و ارز پورسانت تا تأیید مالک تعیین/فعال نمی‌شوند.

### 2.7 Task، Audit و Notification — M00/M08/M11

- AuditEvent مشترک actor، company، subject، event، before/after و زمان را ثبت می‌کند؛ رویداد مالی/موجودی نباید صرفاً Log فنی باشد.
- Task لینک polymorphic فقط با allowlist و اعتبارسنجی هم‌شرکتی والد پذیرفته می‌شود.
- Notification inbox با event source و DB dedupe پایدار ساخته می‌شود؛ Cache فقط بهینه‌سازی است.
- URL اعلان در زمان مشاهده دوباره Policy مقصد را اجرا می‌کند.
- Scheduler/Queue باید روی هاست اشتراکی قابل اجرا باشد و به worker دائمی/Redis فرضی وابسته نشود.

## 3. شکاف‌های سراسری اولویت‌بندی‌شده

| اولویت | شکاف | ماژول‌ها | اثر |
|---|---|---|---|
| P0 | Company Context و Authorization یکپارچه برای همه کانال‌ها | M00/M01/M02 | خطر نشت داده و مجوز |
| P0 | Party مرجع و قید هم‌شرکتی ارجاعات | M03/M04/M06/M07/M09/M12 | چند هویت موازی و ارجاع بین‌شرکتی |
| P0 | Snapshot/Revision و Change Order | M05/M06/M07/M09/M12 | از دست رفتن صحت تاریخی سفارش/مالی |
| P0 | Ledger و Reservation اتمیک و قابل بازسازی | M06/M07/M10 | مغایرت موجودی/تولید |
| P0 | Decimal و ثبت پرداخت هم‌زمان | M09 | خطای مبلغ و overpayment |
| P1 | Workflow/Transition History | M04/M06/M07/M08 | وضعیت ناسازگار و نبود Audit |
| P1 | Partyمحور کردن پیمانکار/نماینده بیرونی | M03/M07/M12 | تکرار داده و دسترسی غیرقابل کنترل |
| P1 | Commission multi-beneficiary و نسخه سیاست | M06/M09/M12 | استحقاق و تسویه غیرقابل اتکا |
| P1 | DB dedupe برای Notification و Idempotency عمومی | M00/M08/M10/M11 | اجرای تکراری Job/رویداد |
| P1 | KPI Query/Export مجوزمحور و کارا | M04/M11/M12 | گزارش غلط یا نشت داده |
| P1 | Shared-hosting spike و deploy proof | Platform | انتخاب معماری نامتناسب با هاست |
| P2 | ترجیحات کانال، Escalation، PDF و Export پیشرفته | M09/M11 | تکمیل تجربه و عملیات ثانویه |

این اولویت‌بندی معماری اولیه است؛ زمان/هزینه و ترتیب انتشار نهایی پس از دریافت محدودیت واقعی هاست و تصمیم مالک تثبیت می‌شود.

## 4. قراردادهای فنی افقی

### 4.1 یکپارچگی DB
- برای هر رابطه عملیاتی، فقط FK ساده کافی فرض نشود؛ تعلق والد و فرزند به company یکسان باید در Action و در صورت امکان با قید DB enforce شود.
- یکتایی‌های شرکت‌محور مانند کد، شماره سند، idempotency و revision در DB تعریف شوند.
- Morph relationها allowlist داشته باشند؛ company_id و نوع/مالک والد در سرور resolve شوند.
- عملیات چندمرحله‌ای مالی/موجودی/تبدیل سند در Transaction با lock مناسب انجام شوند.
- هیچ مانده مالی/موجودی با ویرایش دستی به‌عنوان منبع حقیقت تغییر نکند.

### 4.2 اعداد، تاریخ و ارز
- ورودی فارسی/عربی در boundary normalize شود و در DB ارقام ASCII ذخیره شوند؛ ورودی نامعتبر بی‌صدا صفر نشود.
- تاریخ شمسی فقط لایه نمایش/ورودی است؛ storage و API از قالب استاندارد و timezone مشخص استفاده می‌کنند.
- ارز هر سند اجباری و صریح است؛ IRR و نمایش ریال/تومان و rounding باید در M06/M09/M12 یکسان شود.
- پول با Decimal/integer دقیق محاسبه شود؛ quantity نیز واحد سنجش و دقت تعریف‌شده داشته باشد.

### 4.3 Audit و Event
- Domain Eventهای مالی/موجودی/سفارش در تراکنش و با outbox یا الگوی قابل اتکا منتشر شوند.
- Jobهای مصرف‌کننده idempotent باشند؛ retry نباید سند/پرداخت/رزرو/اعلان تکراری بسازد.
- Audit و Ledger append-only هستند؛ اصلاح با رویداد جبرانی انجام می‌شود.

## 5. مسیر اجرای پیشنهادی

1. تکمیل ممیزی عمیق M00–M02 و ممیزی Route/Policy/Migration/Job برای تمام نقاط حساس.
2. تثبیت قرارداد Party، Company Context، Permission Resolver و FKهای هم‌شرکتی.
3. تثبیت Product Revision، Rule/Price Snapshot، Quote/Order Revision و Change Order.
4. تثبیت Workflow/Production Routing و مرز WorkPackage/ProductionRun.
5. تثبیت Inventory Ledger/Reservation و تطبیق با M07/M06.
6. تثبیت Invoice/PaymentAllocation/Refund و دقت پولی.
7. تثبیت Attribution/Commission به‌عنوان طراحی، بدون فعال‌کردن نرخ تا تأیید مالک.
8. انجام spike UI/hosting و سپس ساخت vertical slice.
9. پس از موفقیت vertical slice و deploy آزمایشی، scaffold و توسعه مرحله‌ای ماژول‌ها.

## 6. دروازه‌های تصمیم مالک

موارد زیر فعلاً نباید توسط عامل حدس زده شوند:

- مشخصات و محدودیت‌های واقعی هاست اشتراکی و امکان cron/queue/storage/SSH/document root.
- انتخاب Inertia یا Blade + Vue/API پس از spike.
- سیاست مالیاتی، تخفیف، واحد ریال/تومان و rounding.
- سیاست صدور چند فاکتور از یک سفارش و پرداخت چندفاکتوری.
- فرمول مصرف داخله و اقلام همراه.
- مبنای استحقاق، نرخ و تقسیم پورسانت.
- سیاست پورتال و دسترسی نماینده بیرونی.

## 7. وضعیت اعتبارسنجی

- اسناد M00 تا M12 بررسی شده‌اند؛ سطح شواهد و عمق بررسی هر ماژول در سند خودش آمده است.
- این سند تحلیل اتصال بین‌ماژولی است و هنوز schema نهایی، Migration یا کد اجرایی ایجاد نمی‌کند.
- تست‌های قدیمی در این مرحله اجرا نشده‌اند؛ نام فایل تست فقط شواهد وجود تست است.
- hamarehOffice هنوز scaffold اجرایی Laravel/Vue ندارد؛ هیچ تست runtime/build/deploy جدیدی انجام نشده است.

**نتیجه:** پیش از ساخت رابط Vue، باید قراردادهای شرکت/هویت، Party، Snapshot، Workflow، Ledger و مالی یکپارچه شوند. UI spike مهم است، اما بعد از روشن‌شدن این قراردادها و بررسی محدودیت واقعی هاست انجام می‌شود.
