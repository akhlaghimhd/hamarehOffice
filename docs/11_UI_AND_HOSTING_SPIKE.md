# Spike انتخاب رابط Vue و سازگاری با هاست اشتراکی

**وضعیت:** پیشنهاد فنی موقت؛ انتخاب نهایی استقرار تا دریافت مشخصات واقعی هاست تأیید نشده است.  
**تاریخ بررسی منابع:** 2026-11-21  
**مبنای محصول:** Laravel + Vue 3، یک هویت واحد، UI فارسی RTL، مقصد هاست اشتراکی، Node فقط برای build.

## 1. نتیجه پیشنهادی

برای نسخه اول hamarehOffice، **Laravel + Inertia 3 + Vue 3 در یک مخزن و یک origin** گزینه ترجیحی است؛ با این شروط:

- SSR فعال نشود؛ خروجی Vite در زمان توسعه/build تولید و فایل‌های build شده deploy شوند.
- Node فقط در محیط توسعه یا CI برای build استفاده شود؛ روی هاست Node server اجرا نشود.
- احراز هویت مرورگر از Session استاندارد Laravel و همان دامنه استفاده کند؛ هویت یا login موازی ساخته نشود.
- Laravel مرجع نهایی Company Context، Permission، Validation و منطق دامنه باقی بماند.
- UI با Design System اختصاصی Vue/CSS ساخته شود؛ هیچ Admin Template یا پنل‌ساز تحمیل نشود.
- API عمومی/موبایل در آینده، در صورت نیاز واقعی، به‌صورت مسیرهای جدا در همان Laravel و با همان Action/Policyها افزوده شود؛ نیاز به جدا کردن SPA در فاز اول وجود ندارد.

این توصیه بر مبنای نیاز فعلی CRM خصوصی و یکپارچه است، نه الزام دائمی. اگر هاست Inertia/Vite asset deployment یا مسیر امن document root را پشتیبانی نکند، باید نتیجه spike بازنگری شود.

## 2. شواهد رسمی فنی

- مستند Laravel 13، Starter Kit رسمی Vue را مبتنی بر Vue 3 و Inertia معرفی می‌کند و توضیح می‌دهد Inertia مسیر/Controller و احراز هویت Laravel را با frontend مدرن Vue در یک repository ترکیب می‌کند. این کیت در حال حاضر Tailwind و shadcn-vue نیز دارد؛ استفاده از UI آماده آن برای هم‌ره الزامی نیست و می‌توان فقط الگوی اتصال را انتخاب کرد.  
  منابع: [Laravel 13 Starter Kits](https://laravel.com/framework/docs/13.x/starter-kits)، [Laravel 13 Frontend](https://laravel.com/framework/docs/13.x/frontend)
- Laravel Vite، CSS و JavaScript را به فایل‌های production-ready تبدیل می‌کند؛ Node برای نصب وابستگی و build لازم است، نه برای سرویس‌دهی runtime فایل‌های build شده. SSR در مستندات به‌عنوان مسیر جداگانه با اجرای Node server معرفی شده است؛ برای CRM خصوصی هم‌ره در فاز اول لازم نیست.  
  منبع: [Laravel 13 Vite](https://laravel.com/framework/docs/13.x/vite)
- Laravel Sanctum برای SPA جدا از backend از Session cookie و CSRF پشتیبانی می‌کند، اما تنظیم stateful domains، CORS/cookie و CSRF لازم است؛ در حالت SPA و API باید دامنه سطح بالا مشترک باشد. این مسیر شدنی است ولی نسبت به Inertia یکپارچه، اجزای بیشتری برای پیکربندی و نگهداری دارد.  
  منبع: [Laravel 13 Sanctum](https://laravel.com/framework/docs/sanctum)
- Laravel 13 حداقل PHP 8.3 و Extensionهای Ctype، cURL، DOM، Fileinfo، Filter، Hash، Mbstring، OpenSSL، PCRE، PDO، Session، Tokenizer و XML می‌خواهد. مقصد باید document root را به `public/` هدایت کند؛ انتقال `index.php` به ریشه پروژه توصیه نمی‌شود. `storage` و `bootstrap/cache` باید برای PHP قابل نوشتن باشند.  
  منبع: [Laravel Deployment](https://laravel.com/framework/docs/deployment)
- Laravel Scheduler با یک Cron entry در سرور قابل اجراست؛ فایل زمان‌بندی داخل پروژه باقی می‌ماند.  
  منبع: [Laravel 13 Task Scheduling](https://laravel.com/framework/docs/13.x/scheduling)
- راهنمای cPanel نمونه‌ای از اجرای Scheduler با cron و اجرای محدود Queue worker از طریق cron را نشان می‌دهد؛ در دسترس بودن این قابلیت‌ها به پلن/تنظیمات هاست وابسته است و باید از شرکت هاست تأیید شود.  
  منبع: [cPanel Cron Jobs](https://support.cpanel.net/hc/en-us/articles/360053606793-How-to-create-or-edit-cron-jobs-in-the-cPanel-user-interface)

## 3. مقایسه گزینه‌ها

| معیار | Inertia + Vue 3 | Blade + Vue/API |
|---|---|---|
| یکپارچگی Laravel | بالا؛ Route/Controller معمولی و Inertia response | نیازمند قرارداد API و Blade shell |
| Auth و Session | همان چرخه وب Laravel | برای SPA/API معمولاً Sanctum stateful + CSRF/CORS |
| Validation و خطا | چرخه Validation/Redirect Laravel با Inertia | طراحی/تطبیق پاسخ JSON و مدیریت state در Vue |
| Routing | Laravel route و Inertia navigation | Laravel API + routing سمت Vue یا hybrid |
| UI/CSS آزاد | کامل؛ Inertia کتابخانه UI نیست | کامل |
| موبایل | وابسته به طراحی Vue؛ محدودیت ذاتی ندارد | وابسته به طراحی Vue؛ محدودیت ذاتی ندارد |
| Build | Vite؛ build فایل استاتیک | Vite؛ build فایل استاتیک |
| Node runtime | لازم نیست، اگر SSR خاموش باشد | لازم نیست، اگر SPA استاتیک build شود |
| پیچیدگی شروع | کمتر برای برنامه Laravel-first | بیشتر به‌علت قرارداد API و احراز هویت SPA |
| API مستقل در آینده | قابل افزودن در همان Laravel | از ابتدا API محورتر است |
| ریسک چند کلاینت | مناسب یک UI اصلی | مناسب‌تر وقتی چند frontend مستقل از ابتدا قطعی باشند |

## 4. تصمیم‌های پیشنهادی پیاده‌سازی

### Frontend
- Vue 3 Composition API و TypeScript برای صفحات پیچیده و قراردادهای داده.
- Inertia برای navigation و انتقال props؛ Vue فقط presentation و interaction.
- Design System اختصاصی با CSS variables/tokens، کامپوننت‌های پایه و الگوی فرم/جدول موبایل.
- هیچ Admin Template، CRUD generator یا component library اجباری وارد پروژه نشود.
- Tailwind اختیاری است؛ اگر استفاده شود فقط ابزار CSS است و جایگزین طراحی اختصاصی نیست.
- SSR، Node server، Nuxt و SPA repository جدا در فاز اول اضافه نشوند.

### Backend
- Laravel web routes/controllers و Inertia response.
- Action/Service/Policy/Request برای تمام منطق و اعتبارسنجی.
- Session و CSRF استاندارد؛ Company Context و Permission در سرور و هر درخواست.
- Inertia shared props فقط برای اطلاعات نمایشی محدود مانند User، شرکت جاری، مجوزهای نمایشی و اعلان unread؛ هیچ prop به‌عنوان مجوز نهایی اعتماد نشود.
- فایل‌ها، Export، فرم‌ها و pagination از endpointهای Laravel استفاده کنند.
- برای مصرف‌کننده بیرونی آینده، API versioned و Sanctum فقط در صورت نیاز اضافه شود؛ همان Action/Policyها استفاده شوند.

### Shared Hosting
- build در Linux توسعه یا CI انجام شود؛ فقط artifactهای لازم و فایل‌های PHP deploy شوند.
- هاست فقط PHP، MariaDB، web server و فایل‌های build شده را سرویس دهد؛ Node نصب/runtime لازم نیست.
- document root باید به public امن اشاره کند؛ اگر امکان تنظیم آن وجود ندارد، طرح استقرار جایگزین باید با آزمون امنیتی جدا تصویب شود.
- ابتدا Queue driver برابر database یا sync بر اساس قابلیت هاست انتخاب شود؛ Redis پیش‌فرض نیست.
- Scheduler فقط در صورت دسترسی Cron قابل اتکا فعال شود؛ یک cron اجرای `schedule:run` در هر دقیقه.
- اگر worker دائمی مجاز نیست، Jobها باید کوتاه، idempotent و قابل اجرای batch/cron باشند؛ در غیر این صورت عملیات حیاتی را به Queue وابسته نمی‌کنیم.
- فایل‌ها در storage خصوصی قرار گیرند؛ symlink/public storage تا زمان تأیید هاست فرض نشود.
- deploy شامل Composer install مناسب، build artifact، backup، migration کنترل‌شده، cache، health check و rollback باشد.

## 5. دروازه‌های آزمون پیش از تصویب نهایی

### آزمون محلی
- ورود و خروج، CSRF و session.
- Switch Company و پاک شدن مجوزهای قبلی.
- صفحه فهرست، جزئیات و فرم با validation سرور.
- آپلود/دانلود خصوصی با Policy.
- نمایش RTL، فونت، اعداد فارسی و تاریخ شمسی.
- build تولیدی بدون dev server و بدون SSR.
- refresh مستقیم URL و navigation بین صفحات.
- pagination/filter و مدیریت خطا/401/419.

### آزمون روی هاست هم‌ارز مقصد
- PHP و Extensionهای مورد نیاز Laravel 13.
- Composer/SSH یا روش build و upload vendor.
- document root امن و Rewrite به public/index.php.
- writable بودن storage و bootstrap/cache.
- اتصال MariaDB و Migration transaction/index.
- Vite manifest و asset URL/HTTPS.
- Cron، scheduler و در صورت نیاز database queue.
- upload size، execution time، memory limit، backup و rollback.

**شرط قبولی:** build و درخواست‌های واقعی روی محیط هاست بدون Node runtime، بدون دسترسی به فایل‌های خارج public و با حفظ Session/CSRF/Company Context موفق باشند.

## 6. اطلاعات لازم از مالک/هاست

این موارد از مستندات عمومی قابل حدس نیستند و باید از پلن واقعی دریافت شوند:

- نام شرکت هاست و نام پلن.
- PHP قابل انتخاب (ترجیحاً 8.4 مطابق baseline پروژه) و فهرست Extensionها.
- امکان SSH/Terminal و اجرای Composer/Artisan.
- امکان تنظیم Document Root دامنه روی `public` یا مسیر سفارشی.
- نسخه MariaDB/MySQL و محدودیت حجم/اتصال.
- امکان Cron با حداقل بازه یک دقیقه.
- اجازه اجرای Queue worker کوتاه‌مدت یا محدودیت process.
- مسیر/سهمیه Storage، امکان symlink و محدودیت upload.
- memory_limit، max_execution_time و max_input_vars.

## 7. نتیجه و وضعیت

- **پیشنهاد موقت:** Inertia 3 + Vue 3 + Laravel در یک repository، same-origin، SSR خاموش و Vite build-time.
- **عدم انتخاب:** SPA جدا با API/Sanctum برای نسخه اول؛ مگر آنکه چند frontend مستقل یا محدودیت مشخص هاست این انتخاب را توجیه کند.
- **عدم تغییر:** هنوز package نصب، scaffold یا فایل اجرایی ایجاد نشده است.
- **نیازمند مالک:** مشخصات واقعی هاست و تأیید اینکه دسترسی document root/Cron/Composer در پلن موجود است.
- **گام بعد:** پس از دریافت مشخصات هاست، vertical slice کوچک و deploy آزمایشی؛ فقط پس از موفقیت، انتخاب UI قطعی و scaffold آغاز شود.
