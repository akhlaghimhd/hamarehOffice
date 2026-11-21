# M05 — Catalog & Product Configuration | کاتالوگ و پیکربندی محصول

**منبع نیازمندی تاریخی:** `docs/reference/legacy-hamarehCRM/system/modules/M05_Catalog_Product.md`  
**منابع معماری تاریخی:** `docs/reference/legacy-hamarehCRM/CATALOG_ARCHITECTURE.md` و `docs/reference/legacy-hamarehCRM/system/04_DYNAMIC_PRODUCT_CONFIGURATION.md`  
**منبع کد بررسی‌شده:** `hamarehCRM` / `feature/filament5-upgrade` / `25ba93d0408b4b641e7fb52989e89557381cbaed`  
**وضعیت انتقال:** اسناد دامنه، مدل‌های کاتالوگ، Configurator، Migrationهای منتخب، Seederها، Resourceهای منتخب و تست Configurator بررسی شدند؛ در hamarehOffice پیاده‌سازی نشده است. ممیزی همه جداول پایه، قیمت‌گذاری، انتشار، سفارش و تولید کامل نیست.

## هدف

ساخت موتور عمومی و داده‌محور برای تعریف و پیکربندی محصولات چندماهیتی. نوتوپیا اولین نمونه عمیق موتور است، نه قالبی که همه محصولات و خدمات باید از آن پیروی کنند. نوع محصول، ویژگی‌ها، گزینه‌ها، ماتریس مجاز، قواعد، قیمت و نسخه انتشار باید تا حد ممکن با داده مدیریت شوند.

## اصول قطعی دامنه

1. ProductType، Family، Season، Model، Attribute، Option و Rule داده‌های قابل مدیریت‌اند.
2. کد ProductType شناسه پایدار است؛ نباید مبنای switch/case و شرط اختصاصی برای رفتار هر محصول باشد.
3. Family/Season/Model برای محصولاتی که نیاز دارند اختیاری است؛ محصول ساده یا خدماتی نباید مجبور به ساخت این سلسله‌مراتب شود.
4. Attribute دارای نوع داده، واحد، اعتبارسنجی، منبع گزینه، ترتیب، الزام و قواعد نمایش است.
5. ماتریس مجاز و Ruleها ترکیب معتبر را تعیین می‌کنند؛ اعتبارسنجی سمت سرور مرجع نهایی است.
6. فرم پیکربندی از Metadata ساخته می‌شود و منطق اعتبارسنجی مستقل از UI است.
7. Variant فقط در صورت نیاز به SKU، موجودی، قیمت یا تولید مستقل مادی‌سازی می‌شود؛ تولید همه ترکیب‌های Cartesian ممنوع است.
8. تعریف منتشرشده نسخه‌دار و immutable است؛ تغییر با Draft/Revision جدید انجام می‌شود.
9. Quote/Order Line باید Snapshot کامل و نسخه‌دار از تعریف، انتخاب‌ها، قیمت و Ruleهای مؤثر داشته باشد.

## مفاهیم داده‌ای و قرارداد دامنه

### ProductType و ساختار تجاری
- ProductType دارای کد پایدار، عنوان، قابلیت‌ها و سیاست‌ها است.
- ProductLine، ProductFamily، CatalogSeason و ProductModel لایه‌های اختیاری‌اند؛ نوتوپیا می‌تواند سلسله‌مراتب فعلی خود را حفظ کند.
- هر نوع محصول می‌تواند ساختار ساده یا سلسله‌مراتب متفاوت داشته باشد؛ اجبار به ProductModel برای خدمات و کالاهای ساده ممنوع است.
- Product و ProductVariant دو مفهوم جدا هستند؛ Variant هویت مستقل عملیاتی فقط در صورت نیاز واقعی دارد.

### Attribute و Option
- Attribute شامل code، عنوان فارسی، data_type، واحد، required، default، validation، منبع گزینه، sort و metadata نمایش است.
- انواع پایه می‌توانند text، integer، decimal، boolean، date، single_select، multi_select، measurement و reference باشند.
- Option Pool برای اجزا/خدمات/امکانات قابل استفاده مجدد است؛ اتصال مجاز به ProductType/Model با pivotهای دارای وضعیت، حداقل/حداکثر تعداد، پیش‌فرض، پیشنهادی، ترتیب و بازه اثر انجام می‌شود.
- ویژگی‌های یک Option نیز باید Metadataمحور باشند؛ ساختار ویژگی فقط در JSON آزاد و بدون Schema اعتبارسنجی کافی نیست.

### ماتریس مجاز و Rule Engine
- ماتریس پایه مجاز بودن Option، Attribute یا Variant را مشخص می‌کند.
- Ruleها مکمل ماتریس‌اند و شامل scope، condition، action/effect، priority، بازه اثر، پیام فارسی و test cases هستند.
- Ruleهای پایه شامل required/default، include/exclude، mutually_exclusive، depends_on، min/max quantity، conditional_visibility، validation/warning/block، calculated_value/price_adjustment و routing_selection هستند.
- Rule به‌صورت DSL محدود و JSON Schema نسخه‌دار ذخیره می‌شود؛ فقط عملگرها و Actionهای allowlist شده اجرا می‌شوند.
- اجرای PHP، SQL، eval یا Expression دلخواه از داده ممنوع است.
- ارزیابی Rule باید deterministic، قابل توضیح، تست‌پذیر و مستقل از ترتیب نامطمئن Query باشد.

### قیمت
- قیمت می‌تواند از base price، SKU/Option، مقدار، مشتری/کانال، تاریخ اثر، تخفیف و هزینه‌های مصوب تشکیل شود.
- Price Breakdown باید اجزای قیمت و Ruleهای مؤثر را نشان دهد.
- قیمت فروش و بهای تمام‌شده جدا هستند.
- واحد پول مرجع و دقت ذخیره‌سازی باید با قرارداد مالی پروژه هماهنگ شود؛ سند معماری قدیمی IRR را ذکر می‌کند و تفاوت نمایش ریال/تومان نیازمند تثبیت است.
- قیمت Override شده از سمت کاربر/کلاینت بدون مجوز و سیاست قیمت‌گذاری نباید مرجع قطعی شود.

### انتشار، Revision و Snapshot
- چرخه انتشار حداقل draft → validated → published → superseded/archived است.
- انتشار فقط پس از اعتبارسنجی Attributeها، Ruleها، ماتریس مجاز و تست‌های نمونه انجام می‌شود.
- Revision منتشرشده immutable است؛ ویرایش با Revision جدید انجام می‌شود.
- Quote به Revision مشخص متصل می‌شود و OrderLine علاوه بر ارجاع، Snapshot مستقل و کامل نگه می‌دارد.
- Snapshot باید مقادیر انتخابی، عناوین/کدهای قابل نمایش، قیمت تفکیکی، واحد پول، نسخه تعریف و Ruleهای اعمال‌شده را حفظ کند.
- کپی Model باید وابستگی‌های آن را با شناسه‌های مقصد و در محدوده همان شرکت کپی کند؛ اشتراک ناخواسته یا کپی ناقص Pivotها ممنوع است.

## شواهد مشاهده‌شده در کد قدیمی

| قابلیت | شواهد | برداشت مجاز |
|---|---|---|
| ProductLine | `app/Models/ProductLine.php` | company-scoped؛ کد، نام، نوع، برند، واحد، قیمت پایه/تمام‌شده و JSON attributes دارد |
| ProductFamily | `app/Models/ProductFamily.php` | company-scoped، دارای کد، نام، ترتیب و وضعیت فعال |
| CatalogSeason | `app/Models/CatalogSeason.php` | company-scoped، کد، نام، برچسب سال، تاریخ شروع/پایان و SoftDeletes |
| ProductModel | `app/Models/ProductModel.php` | به Line، Family و Season و نیز Size، CoverSku و Option متصل است؛ برخی فیلدهای اختصاصی نوتوپیا مانند includes_* نیز دارد |
| ProductOption | `app/Models/ProductOption.php` | استخر Option با نوع، قیمت، موجودی، model_scope و JSON attributes دارد |
| Option Attribute | `ProductOptionAttribute.php` | نوع، گزینه‌ها، مقدار، الزام و اثر بر موجودی را ذخیره می‌کند؛ فقط به ProductOption متصل است |
| Option Variant | `ProductOptionVariant.php` | ترکیب، برچسب، قیمت، موجودی و SKU برای Option دارد |
| CatalogRule | `app/Models/CatalogRule.php` | company scope، product_line، scope، condition_json، effect_json، level و message دارد |
| Configurator | `app/Services/CatalogConfigurator.php` | schema، validate، price و snapshot ارائه می‌کند و Model را در محدوده company_id جستجو می‌کند |
| Migration Rule | `2026_11_08_172551_create_catalog_rules_table.php` | جدول Rule با condition/effect JSON و سطح/پیام ایجاد شده است |
| Migration Attribute | `2026_11_08_172553_alter_product_catalog_for_dynamic_attributes.php` | ستون‌های JSON attributes به مدل/سایز/جلد/Option و product_line_id به چند جدول افزوده شده‌اند |
| Migration Season | `2026_11_09_173550_02_create_catalog_seasons_table.php` | یکتایی company_id + code و ایندکس وضعیت فعال وجود دارد |
| Seeder معماری | `CatalogArchitectureSeeder.php` | Family و Seasonهای نمونه را در یک شرکت فعال Seed می‌کند |
| Seeder نوتوپیا | `NotopiaCatalogSeeder.php` و `FreshCatalogDemoSeeder.php` | داده‌های مدل، جلد، بسته‌بندی، Option، ماتریس مجاز و Rule نمونه برای نوتوپیا وجود دارد |
| UI کاتالوگ | Resourceهای ProductModel، ProductOption، ProductFamily، ProductLine، CatalogSeason و CatalogRule | مدیریت داده‌ها در Filament انجام می‌شود؛ برخی Resourceها محدودیت اختصاصی Notopia دارند |
| تست Configurator | `tests/Feature/CatalogConfiguratorTest.php` | Schema مجاز، رد سایز خارج ماتریس و Snapshot انتخاب/قیمت را تست می‌کند |

## محدودیت‌ها و ریسک‌های مشاهده‌شده

1. **ProductType مستقل مشاهده نشد:** در مدل‌ها و فایل‌های بررسی‌شده، موجودیت عمومی ProductType با قابلیت‌ها و سیاست‌های داده‌ای اثبات نشده است.
2. **Attribute عمومی کامل وجود ندارد:** JSON attributes و ProductOptionAttribute وجود دارند، اما مدل عمومی Attribute/AttributeOption با نوع داده، واحد، اعتبارسنجی، اتصال به ProductType و قواعد نمایش در شواهد منتخب مشاهده نشد.
3. **Configurator دارای allowlist سخت‌کدشده است:** `validate()` فقط `notopia`، `cheleva`، `service` و `other` را می‌پذیرد؛ این با اصل تعریف ProductType صرفاً از داده مغایر است.
4. **Rule Engine کامل اثبات نشده:** CatalogRule مدل و UI دارد، اما Configurator بررسی‌شده Ruleهای condition/effect را ارزیابی نمی‌کند. UI نیز توضیح می‌دهد که اجرای سخت‌گیرانه‌تر قواعد به آینده موکول شده است.
5. **قواعد ماتریس بخشی از مسیر اعتبارسنجی نیستند:** Configurator سایز/جلد/Optionهای متصل به Model و min/max تعداد Option را کنترل می‌کند؛ اما از شواهد منتخب اجرای کامل required/forbidden، mutually exclusive، dependency، visibility، warning/block و routing اثبات نمی‌شود.
6. **قیمت‌گذاری ناقص است:** `price()` قیمت پایه مدل یا unit_price ورودی را برمی‌گرداند؛ قیمت Optionها، تعداد، Ruleهای قیمت، PriceList، مشتری/کانال و تاریخ اثر در این سرویس محاسبه نمی‌شوند.
7. **Snapshot ناقص از قرارداد نسخه‌دار است:** Snapshot فعلی انتخاب Model/Size/Cover/Option و قیمت را ذخیره می‌کند، اما شناسه Revision منتشرشده، نسخه Ruleها و PriceList و جزئیات کامل تعریف Attributeها در خروجی مشاهده‌شده نیست.
8. **چرخه Revision انتشار اثبات نشده:** status مدل و `isPublishable()` وجود دارد؛ اما Draft/Validated/Published immutable و ایجاد Revision جدید از کدهای بررسی‌شده اثبات نمی‌شود.
9. **اجبار سلسله‌مراتب در isPublishable:** `ProductModel::isPublishable()` وجود Family و Season و حداقل یک Size و CoverSku را لازم می‌داند؛ این برای محصول ساده/خدماتی که این سلسله‌مراتب را نیاز ندارند مناسب نیست.
10. **فیلدهای اختصاصی محصول در مدل عمومی باقی مانده‌اند:** `has_special_paper`، `includes_erasable_pen` و `includes_cloth` نمونه‌هایی از منطق خاص نوتوپیا هستند و نباید الگوی همه ProductTypeها شوند.
11. **محدودسازی UI به نوتوپیا:** `CatalogRuleResource::getEloquentQuery()` با کد ثابت `notopia` فیلتر می‌کند و پیام UI نیز قواعد کاتالوگ نوتوپیا را معرفی می‌کند.
12. **کپی Model نیازمند ممیزی است:** متد `duplicateForCatalog()` برخی Pivotها را کپی می‌کند؛ اما وابستگی‌های دیگر مانند Ruleها، ویژگی‌ها و موجودیت‌های مرتبط و هم‌شرکتی بودن همه FKها باید بررسی شود. مسیر دیگری از UI نیز منطق کپی جدا دارد.
13. **Variant عمومی محصول وجودش اثبات نشده:** ProductOptionVariant برای ترکیب‌های یک Option وجود دارد؛ این معادل ProductVariant عمومی با SKU/موجودی/قیمت مستقل نیست.
14. **قیمت float:** Configurator قیمت را به float تبدیل می‌کند؛ برای مبالغ مالی باید نوع عددی دقیق و سیاست گردکردن/دقت مشخص باشد.
15. **تست‌ها محدودند:** سه سناریوی تست Configurator خوانده شد؛ تست Rule Engine، ProductType دلخواه، Revision/Publish، قیمت Option، عدم تغییر Snapshot پس از Revision جدید و محصول غیرنوتوپیا در این فایل دیده نشد.
16. **UI وابسته به Filament:** Resourceها و صفحه NotopiaCatalog به Filament متصل‌اند و در پروژه جدید فقط منطق دامنه و قراردادها قابل استفاده‌اند.

## قرارداد معماری hamarehOffice

### مرزهای داده و مدل مفهومی
- ProductType، Attribute، Option، Rule، PriceList و Revision به‌صورت داده‌ای و در محدوده `company_id` مدیریت می‌شوند؛ از `tenant_id` استفاده نمی‌شود.
- Family/Season/Model اختیاری‌اند و تنها در ProductTypeهایی فعال می‌شوند که قابلیت سلسله‌مراتب دارند.
- اتصال‌های ماتریس مجاز دارای FK و قیود هم‌شرکتی هستند؛ شناسه یک Option از شرکت دیگر نباید در Model جاری پذیرفته شود.
- داده‌های منتشرشده از Draft جدا و Revisionهای منتشرشده immutable هستند.
- Variant فقط با نیاز واقعی هویت مستقل عملیاتی ساخته می‌شود؛ ترکیب‌های مجاز می‌توانند بدون رکورد Variant محاسبه شوند.

### قرارداد سرویس مستقل از UI
- `schema(companyId, productTypeId, revisionId, context)`: Metadata و گزینه‌های مجاز را برگرداند.
- `validate(companyId, revisionId, payload, context)`: همه قواعد سمت سرور را اجرا و خطا/هشدار قابل توضیح برگرداند.
- `price(companyId, revisionId, payload, context)`: قیمت تفکیکی و نسخه‌دار محاسبه کند.
- `snapshot(companyId, revisionId, payload, context)`: Snapshot کامل و immutable برای Quote/Order بسازد.
- Import، API، رابط Vue و هر مسیر دیگر باید همین سرویس را فراخوانی کنند؛ اعتبارسنجی تکراری در UI ممنوع است.

### امنیت Rule Engine
- DSL محدود و Schema نسخه‌دار با عملگرها و Actionهای allowlist.
- عدم اجرای کد یا Query دلخواه از داده.
- محدودیت عمق/تعداد Rule، زمان ارزیابی و اندازه Payload.
- تست نمونه و توضیح نتیجه هر Rule در زمان انتشار و در صورت نیاز در Price Breakdown.
- شکست در Rule validation باید fail-closed باشد؛ خطای Rule نباید به پذیرش ترکیب نامعتبر منجر شود.

## سناریوهای پذیرش

- [ ] تعریف ProductType جدید بدون تغییر PHP یا Migration.
- [ ] تعریف Attribute جدید با نوع داده، واحد، اعتبارسنجی و منبع گزینه و تولید فرم پویا.
- [ ] افزودن ProductType ساده/خدماتی بدون Family/Season/Model.
- [ ] اتصال Optionها به Model/Type با حداقل/حداکثر تعداد و پیش‌فرض/پیشنهادی.
- [ ] رد ترکیب نامعتبر در UI و سمت سرور.
- [ ] اجرای Ruleهای required، exclusion، dependency، quantity، visibility و warning/block.
- [ ] عدم امکان اجرای PHP/SQL یا Expression دلخواه در Rule.
- [ ] قیمت‌گذاری با Option، مقدار، تاریخ اثر و PriceList و ارائه Breakdown.
- [ ] انتشار Revision فقط پس از اعتبارسنجی و تست قواعد.
- [ ] عدم تغییر Snapshot سفارش/پیش‌فاکتور با ویرایش یا انتشار Revision جدید.
- [ ] استفاده نوتوپیا و حداقل یک نوع محصول متفاوت از یک موتور مشترک.
- [ ] کپی مدل و وابستگی‌ها در همان شرکت، بدون اشتراک یا اتصال بین‌شرکتی ناخواسته.
- [ ] مادی‌سازی Variant فقط برای ترکیب‌های دارای نیاز SKU/موجودی/قیمت/تولید مستقل.

## تست و وضعیت اجرا

- فایل تست مشاهده‌شده: `tests/Feature/CatalogConfiguratorTest.php`.
- سه تست Schema ماتریس مجاز، رد سایز خارج ماتریس و Snapshot انتخاب/قیمت خوانده شدند؛ تست‌ها در این بازنگری اجرا نشده‌اند.
- وجود تست در فایل به معنی پوشش کامل دامنه یا اجرای موفق در این بازنگری نیست.
- نتیجه تست اجرایی برای hamarehOffice وجود ندارد؛ پروژه جدید هنوز scaffold نشده است.

## تصمیم‌ها و کارهای باز

1. ProductType و Attribute عمومی در طراحی جدید به‌عنوان موجودیت‌های داده‌محور تعریف شوند.
2. ماتریس مجاز فعلی نوتوپیا حفظ شود، اما در Rule Engine عمومی و مستقل از نام محصول مصرف شود.
3. چرخه Revision و انتشار immutable و قرارداد Snapshot قبل از اتصال M06 طراحی شود.
4. PriceList/PriceRule و دقت پولی با M06/M09 هم‌راستا شوند.
5. سیاست Variant و SKU با M10 موجودی و M07 تولید تطبیق داده شود.
6. فهرست کامل Migrationهای پایه و Pivotها، قیود هم‌شرکتی و مسیرهای کپی/انتشار در ممیزی تکمیلی بررسی شود.
7. تست‌های فعلی در محیط قابل تکرار اجرا و تست‌های Rule/Revision/Price/Variant اضافه شوند.

**جمع‌بندی:** کد قدیمی زیرساخت قابل استفاده‌ای برای کاتالوگ نوتوپیا دارد: Line/Family/Season/Model، Option Pool، Pivotهای مجاز، Rule JSON و Configurator اولیه. اما این پیاده‌سازی هنوز موتور عمومی تعریف‌شده در قرارداد نیست: ProductType/Attribute عمومی، ارزیابی واقعی Rule، قیمت‌گذاری تفکیکی و Revision immutable در شواهد بررسی‌شده کامل نیستند. پروژه جدید باید موتور عمومی را مستقل از نوتوپیا و بدون شرط‌گذاری بر نام محصول طراحی کند.
