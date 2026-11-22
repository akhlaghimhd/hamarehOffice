# راهنمای مهاجرت به Materio Free

**تاریخ:** 2026-11-22  
**هدف:** حذف کامل وابستگی UI قبلی (Inertia / shadcn / Breeze UI) و استقرار Materio Free به‌عنوان پوسته.

## اصل
چیزی از استک UI قبلی به‌عنوان مسیر اصلی باقی نمی‌ماند تا تداخل dependency پیش نیاید.

## فاز A — پاک‌سازی تبلیغ Pro در کلون Materio (لوکال)

مسیر پیشنهادی کلون:
`~/projects/materio-free/typescript-version`

```bash
cd ~/projects/materio-free/typescript-version
grep -Rni "upgrade to pro\|UpgradeToPro\|badge.*[Pp]ro" src --include='*.vue' --include='*.ts' | head -80
```

اقدامات:
1. از فایل(های) navigation عمودی، تمام آیتم‌های فقط-Pro را حذف کن.
2. کامپوننت/دکمه Upgrade to Pro را از layout و داشبورد حذف یا شرطی خاموش کن.
3. `npm run dev` و تأیید کن منو فقط صفحات واقعی Free را دارد.

## فاز B — ساختار مخزن hamarehOffice

پیشنهاد ساختار تمیز:

```text
hamarehOffice/
  docs/
  compose.yaml          # MariaDB لوکال
  project/              # Laravel فقط backend + public برای SPA build
  frontend/             # کپی پاک‌شده Materio Free (سورس UI)
```

یا ادغام تدریجی build خروجی frontend داخل `project/public`.

### کپی frontend

```bash
cd ~/projects/hamarehOffice
rm -rf frontend
cp -a ~/projects/materio-free/typescript-version frontend
cd frontend
# node_modules را دوباره نصب کن اگر لازم است
npm install
```

`frontend` را در git با `.gitignore` مناسب برای `node_modules` و dist اضافه کن.

## فاز C — حذف استک UI قدیمی از Laravel (`project/`)

داخل `project/`:

```bash
cd ~/projects/hamarehOffice/project

# پکیج‌های UI قبلی (نمونه؛ با وضعیت lock واقعی تطبیق بده)
composer remove laravel/breeze --dev 2>/dev/null || true

# بعداً وقتی Inertia دیگر import نمی‌شود:
# composer remove inertiajs/inertia-laravel tightenco/ziggy

npm pkg delete dependencies.@inertiajs/vue3 2>/dev/null || true
# حذف دستی از package.json: @inertiajs/vue3, shadcn مربوطه، radix، tailwind forms اگر فقط برای Breeze بود
```

حذف/بایگانی پوشه‌های UI قدیمی پس از انتقال:
- `resources/js/Pages` (Inertia)
- `resources/js/Layouts` Breeze
- `resources/js/Components` shadcn/Breeze
- `components.json` شادسن

Routeهای Inertia در `routes/web.php` باید به API یا سرو SPA تغییر کنند.

## فاز D — اتصال Laravel ↔ Materio

1. **توسعه لوکال:** Vite dev برای frontend روی پورت جدا (مثلاً 5173)؛ Laravel API روی 8088.
2. **CORS / Sanctum:** برای SPA روی دامنه لوکال تنظیم `SANCTUM_STATEFUL_DOMAINS` و `SESSION_DOMAIN`.
3. **Production:** `npm run build` در `frontend` → خروجی را به `project/public/app` یا ریشه `public` کپی کن؛ Laravel یک route catch-all برای index SPA بدهد (به‌جز `/api/*`).
4. لاگین: endpointهای Laravel + صفحه Login قالب Materio (فرم به API).

## فاز E — دامنه محصول

صفحات ماژول (بازاریابی، مشتری، …) به‌تدریج به‌جای صفحات demo قالب، با داده واقعی API پر می‌شوند. منوی دامنه هم‌ره جایگزین منوی نمونه Materio می‌شود.

## تعریف Done مهاجرت UI

- [ ] هیچ dependency فعال Inertia/shadcn در مسیر اصلی build نیست
- [ ] هیچ بنر/آیتم Upgrade to Pro در UI محصول نیست
- [ ] `npm run build` فرانت بدون خطا
- [ ] Laravel API health + یک صفحه داشبورد Materio با داده/اسکلت دامنه
- [ ] اسناد `04` و `08` با استک جدید هم‌خوان
- [ ] هاست: فقط فایل استاتیک + PHP؛ بدون Node دائم

## نکته
کپی کامل هزاران فایل Materio از طریق ابزار remote در یک مرحله انجام نمی‌شود؛ منبع UI از کلون لوکال Free است که قبلاً نصب شده. این سند قرارداد اجرایی همان مهاجرت است.
