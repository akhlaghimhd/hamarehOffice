# M08 — Workspace & Tasks | میزکار و کارها

**منبع نیازمندی تاریخی:** `docs/reference/legacy-hamarehCRM/system/modules/M08_Workspace_Tasks.md`  
**منبع کد بررسی‌شده:** `hamarehCRM` / `feature/filament5-upgrade` / `25ba93d0408b4b641e7fb52989e89557381cbaed`  
**وضعیت انتقال:** نیازمندی M08، مدل Task/Activity/AuditEvent، Action ذخیره، Policyها، Resource و میزکار تیم، Migration، Seeder مجوز، پیوندهای Task و تست‌های دسترسی/پیوست بررسی شدند؛ در hamarehOffice پیاده‌سازی نشده است. یادآوری و Escalation از M11 خارج از این ماژول است.

## هدف

ارائه فضای کاری تیمی برای کارهای مستقل و وابسته به موجودیت‌های عملیاتی، با ارجاع روشن، اولویت، سررسید، وضعیت، دسترسی سلسله‌مراتبی و تاریخچه قابل ممیزی. Task ابزار واگذاری و پیگیری کار انسانی است؛ Activity رویداد/تعامل ثبت‌شده CRM مانند تماس، جلسه و یادداشت است و این دو نباید در مدل دامنه یکی شوند.

## محدوده و قواعد کسب‌وکار

### Task
- فیلدهای پایه: company_id، subject، description، creator/reporter، assignee، priority، due_at، status، linkable اختیاری، completed_at و تاریخچه.
- وضعیت‌ها: open، in_progress، done، cancelled.
- اولویت‌ها: low، normal، high، urgent.
- لینک اختیاری به Customer/Party، Opportunity، Order یا WorkPackage؛ فهرست انواع مجاز باید محدود و سمت سرور اعتبارسنجی شود.
- Task مستقل از موجودیت والد نیز قابل ایجاد است.
- از صفحه موجودیت والد امکان ایجاد و مشاهده Taskهای مرتبط فراهم باشد.
- تغییر مسئول، وضعیت، اولویت، شرح، سررسید و حذف Audit شود.
- Task می‌تواند از Workflow Transition ساخته شود؛ تکمیل Task به‌تنهایی وضعیت Order یا Production را تغییر نمی‌دهد مگر Action/Transition مجاز جداگانه آن را انجام دهد.

### ارجاع و دسترسی تیمی
- assignee و reporter باید کاربر فعال عضو همان company باشند.
- ارجاع به عضو دیگر نیازمند team.tasks.assign و مدیریت کار دیگران نیازمند team.tasks.manage است.
- مدیر دارای team.tasks.view فقط کارهای اعضای مستقیم و غیرمستقیم درخت مدیریتی مجاز خود را مشاهده می‌کند.
- کاربر دارای tasks.view کارهای خود را می‌بیند؛ مجوز مشاهده تیمی مجوز مدیریت یا ویرایش محسوب نمی‌شود.
- دسترسی مستقیم به رکورد، Query، Action، فایل پیوست و Job باید مستقل از UI کنترل شود.
- نقش مدیر شرکت و Platform Owner باید طبق قرارداد M01 و با Scope روشن اعمال شوند؛ shortcut مدیریتی نباید از محدودیت شرکت عبور کند.

### Audit و Idempotency
- رویدادهای create/update/delete و تغییرات مهم شامل actor، event، occurred_at و before/after باشند.
- ثبت Audit باید در همان تراکنش تغییر دامنه انجام شود یا سازوکار تضمین‌شده‌ای برای عدم گم‌شدن رویداد داشته باشد.
- ایجاد تکراری Task توسط Workflow/Job با idempotency_key در محدوده شرکت کنترل شود.
- در رقابت هم‌زمان، قید یکتایی DB مرجع نهایی است و خطای duplicate باید به نتیجه idempotent قابل بازیابی تبدیل شود.
- Activity نیز تاریخچه تغییرات خود را دارد؛ Timeline Task و Activity قابل نمایش است، اما Activity جای Task نیست.

### پیوست‌ها و میزکار
- پیوست‌ها از Attachment مشترک و Storage خصوصی استفاده کنند؛ دانلود/Preview/Delete با Policy موجودیت والد کنترل شود.
- checksum، uploader، نام اصلی، MIME، اندازه و مسیر ذخیره ثبت شوند؛ Preview فقط MIMEهای مجاز و با nosniff/private cache انجام شود.
- میزکار تیم می‌تواند Task، Activity، سررسید گذشته و Timeline رویدادها را در یک نما تجمیع کند، ولی منبع داده و عملیات آن‌ها جدا بماند.
- فیلترهای پایه: کارهای من، سررسید گذشته، مسئول، وضعیت و اولویت.
- M11 مسئول یادآوری و Escalation است؛ M08 فقط داده سررسید و مقصد/ترجیحات لازم را فراهم می‌کند.

## شواهد مشاهده‌شده در کد قدیمی

| قابلیت | شواهد | برداشت مجاز |
|---|---|---|
| Task Model | `app/Models/Task.php` | company scope، SoftDeletes، وضعیت/اولویت، assignee/reporter، Morph link، Attachment، AuditEvent و completed_at دارد |
| اعتبار عضویت | `Task::booted()` | هنگام save عضویت فعال assignee و reporter در همان شرکت و مقادیر status/priority کنترل می‌شود |
| SaveTask | `app/Actions/Tasks/SaveTask.php` | Action مشترک ایجاد/ویرایش، کنترل شرکت، مجوزها، عضویت assignee، idempotency و ذخیره تراکنشی دارد |
| Task Policy | `app/Policies/TaskPolicy.php` | view/update/delete با company_id و helperهای مالک/مدیر تیم کنترل می‌شود |
| Activity | `app/Models/Activity.php` و Policy | تماس، جلسه، یادداشت و task-type قدیمی را با Opportunity مرتبط می‌کند؛ Activity مستقل از Task مدل شده است |
| AuditEvent | `app/Models/AuditEvent.php` | polymorphic، actor، event، old/new values، metadata و occurred_at دارد |
| Task Resource | `app/Filament/Resources/TaskResource.php` | فهرست، فرم، فیلتر مسئول/وضعیت/اولویت/سررسید گذشته/کارهای من و نمایش موبایل دارد |
| Team Workspace | `app/Filament/Pages/TeamWorkspace.php` | Task و Activity را در نماهای ترکیبی، با فیلتر وضعیت/مالک/نوع و Timeline رویدادها نمایش می‌دهد |
| Linked Task | `app/Filament/Actions/CreateLinkedTaskAction.php` | ایجاد Task وابسته از صفحه موجودیت با SaveTask و idempotency key انجام می‌شود |
| صفحات Create/Edit | صفحات TaskResource | هر دو مسیر ذخیره از SaveTask استفاده می‌کنند |
| Migration | `2026_11_15_190000_create_tasks_and_audit_events.php` | جدول‌های tasks و audit_events، ایندکس‌های شرکت/مسئول/سررسید و unique شرکت/idempotency_key ایجاد می‌شوند |
| Permission Seeder | `database/seeders/TaskPermissionsSeeder.php` | مجوزهای شخصی و تیمی تعریف و مجوزهای پایه به نقش sales شرکت‌ها افزوده می‌شوند |
| پیوست | AttachmentPolicy، AttachmentController و تست‌ها | کنترل والد، دانلود خصوصی، Preview تصویر با MIME allowlist و جلوگیری از نشت بین شرکت‌ها تست شده است |
| تست دسترسی | `tests/Feature/TaskIsolationTest.php` | جداسازی شرکت و لینک Task به Opportunity تست شده است |
| تست تیمی | `tests/Feature/TaskTeamAccessTest.php` | مشاهده مستقیم/غیرمستقیم مدیر، منع ارجاع بین‌شرکتی، Audit تغییرات و idempotency ایجاد تست شده‌اند |

## محدودیت‌ها و ریسک‌های مشاهده‌شده

1. **اعتبارسنجی والد polymorphic ناقص است:** Task مدل فقط عضویت assignee/reporter را کنترل می‌کند؛ در SaveTask نوع و company_id موجودیت linkable بررسی نمی‌شود. بنابراین یک linkable از شرکت دیگر یا نوع غیرمجاز ممکن است به Task متصل شود. این شکاف مهم امنیتی است.
2. **فهرست Morph مجاز تعریف نشده:** ستون linkable_type/ID عمومی است و مدل/Action allowlist مشخصی برای Customer/Opportunity/Order/WorkPackage اعمال نمی‌کند.
3. **idempotency در رقابت هم‌زمان:** SaveTask ابتدا رکورد موجود را query می‌کند و سپس transaction را آغاز می‌کند؛ unique index از duplicate جلوگیری می‌کند، اما بازیابی duplicate-key در رقابت هم‌زمان در Action مشاهده نشد.
4. **ویرایش status از مسیر عمومی ممکن است:** SaveTask status را از ورودی می‌پذیرد و Task::markDone نیز مستقیماً save می‌کند؛ Transitionهای مجاز و کنترل مجوز مستقل برای هر تغییر وضعیت در Action جداگانه اثبات نشد.
5. **حذف/ویرایش و Audit:** Policyها تعریف شده‌اند و تست Audit update وجود دارد؛ مسیرهای مستقیم Model/Job و تضمین Audit برای تمام حذف‌ها و تغییرات همه فیلدها در این بررسی کامل اثبات نشد.
6. **تفاوت Policy و SaveTask:** Policy از canManageTaskOwner استفاده می‌کند؛ SaveTask منطق مجوز و مدیر شرکت را جداگانه پیاده می‌کند. تکرار منطق می‌تواند به اختلاف رفتار API/UI/Job منجر شود.
7. **scope مدیر تیم نیازمند آزمون مرزی است:** تست مدیر مستقیم و غیرمستقیم وجود دارد؛ محدودیت تغییر ساختار درخت مدیریت، کاربر خارج از شاخه، تغییر مدیر و حذف عضویت در حین درخواست باید تکمیلاً تست شود.
8. **shortcut مدیر شرکت:** TaskResource و SaveTask برای مدیر شرکت رفتار ویژه دارند؛ باید با M01 روشن شود این رفتار در تمام کانال‌ها مجاز و محدود به company جاری است.
9. **فیلتر مسئول در Linked Task UI:** Action گزینه‌های همه کاربران فعال را بارگذاری می‌کند، نه فقط اعضای شرکت؛ SaveTask در سرور ارجاع نامعتبر را رد می‌کند، اما UX گزینه‌های نامعتبر نشان می‌دهد.
10. **Activity هنوز TYPE_TASK دارد:** Activity نوع task قدیمی را نگه می‌دارد، درحالی‌که Task مدل مستقل دارد. مهاجرت/سیاست سازگاری داده‌های قبلی باید تعیین شود تا دو مفهوم موازی و مبهم نشوند.
11. **Activity Policy:** Activity به Opportunity متصل است و owner-based scope دارد؛ تضمین هم‌شرکتی Opportunity، Step و Activity در تمام مسیرهای ایجاد/ویرایش در بررسی محدود حاضر اثبات نشده است.
12. **Audit شرکت و رکورد والد:** audit_events دارای company_id و Morph ID/Type است؛ FK مرکب برای هم‌شرکتی بودن auditable و audit_event در Migration دیده نمی‌شود.
13. **یادآوری پیاده نشده:** سند تاریخی M08 نیز یادآوری/Escalation را به M11 موکول کرده است؛ Migration notifications به‌تنهایی اثبات‌کننده زمان‌بندی یا ارسال یادآوری نیست.
14. **تست API/Job/URL:** تست‌های منتخب Feature وجود دارند؛ پوشش جامع دسترسی از API، Job، Livewire و URL مستقیم در این مجموعه اثبات نشده است.
15. **تست‌ها اجرا نشده‌اند:** فایل‌های تست خوانده شده‌اند، اما در این بازنگری اجرا نشده‌اند.
16. **UI وابسته به Filament:** Resource، Page و Actionهای UI مستقیماً قابل انتقال به Vue نیستند؛ SaveTask و قواعد دامنه نیازمند بازطراحی مستقل از UI هستند.

## قرارداد معماری hamarehOffice

### موجودیت‌ها و عملیات
- Task با `company_id`، subject، description، reporter_user_id، assignee_user_id، priority، status، due_at، completed_at و idempotency_key.
- لینک به والد با مدل رابطه کنترل‌شده؛ اگر Morph حفظ شود، allowlist نوع و resolve والد در سرور الزامی است.
- TaskEvent/ AuditEvent تاریخچه immutable تغییرات را ثبت می‌کند؛ قبل/بعد فقط فیلدهای مجاز و بدون افشای اطلاعات حساس.
- Activity در M04/M03 به‌عنوان تعامل CRM باقی می‌ماند و Task جداگانه در M08 مدیریت می‌شود؛ داده‌های Activity::TYPE_TASK قدیمی باید با سیاست Migration روشن شوند.
- Actionهای دامنه: CreateTask، UpdateTask، AssignTask، TransitionTask، CompleteTask، CancelTask، DeleteTask و LinkTask.
- TransitionTask تنها مسیر تغییر وضعیت است؛ تکمیل/لغو دارای actor، زمان، دلیل و کنترل مجوز است.
- SaveTask واحد همه عملیات نباشد؛ هر Action قرارداد و Policy روشن خود را داشته باشد.
- Idempotency در سطح DB و Action با بازیابی duplicate-key و تست concurrency پیاده شود.

### مجوزها
- مجوزهای پایه: tasks.view, tasks.create, tasks.update, tasks.delete.
- مجوزهای تیمی: team.tasks.view, team.tasks.assign, team.tasks.manage.
- تعریف معنای دقیق tasks.view (فقط خود) و team.tasks.view (زیرشاخه مجاز) در M01 ثبت شود.
- مجوز مشاهده تیمی، مجوز ویرایش/ارجاع نیست.
- Scope شرکت در Query/Policy/Action/Job و دسترسی فایل همواره enforce شود؛ فقط company_id معتبر، بدون tenant_id.
- درخت مدیریت باید در محدوده شرکت و بدون امکان دسترسی به خارج از شاخه محاسبه شود.

### پیوست و میزکار
- Attachment از زیرساخت M00 استفاده کند و دسترسی از Policy والد مشتق شود.
- مسیر ذخیره‌سازی خصوصی باشد؛ دانلود/Preview از Controller/endpoint مجاز با بررسی Gate.
- Workspace نمای تجمیعی از Task و Activity ارائه دهد؛ Queryهای هرکدام مجزا و فیلترها/شمارنده‌ها با معنای روشن باشند.
- رابط Vue موبایل‌محور با فیلترهای سریع و عملیات کوتاه طراحی شود؛ پیاده‌سازی UI قدیمی Filament منتقل نشود.

## سناریوهای پذیرش

- [ ] ایجاد Task مستقل و Task مرتبط با Party/Opportunity/Order/WorkPackage.
- [ ] رد linkable با نوع غیرمجاز یا متعلق به شرکت دیگر در Action، API و Job.
- [ ] ارجاع فقط به عضو فعال همان شرکت.
- [ ] کاربر فقط Taskهای مجاز خود را مشاهده کند.
- [ ] مدیر فقط Taskهای اعضای مستقیم و غیرمستقیم مجاز را ببیند؛ نه سایر همکاران.
- [ ] team.tasks.view به‌تنهایی امکان ویرایش/ارجاع ندهد.
- [ ] team.tasks.assign و team.tasks.manage در Action و Policy یکسان enforce شوند.
- [ ] تغییر وضعیت فقط از TransitionTask و با actor/زمان/دلیل انجام شود.
- [ ] تغییرات و حذف Task و Activity دارای Audit before/after باشند.
- [ ] درخواست تکراری Job در اجرای هم‌زمان فقط یک Task بسازد و همان نتیجه را بازگرداند.
- [ ] پیوست Task در Storage خصوصی بماند و از شرکت دیگر قابل دانلود/Preview نباشد.
- [ ] Workspace Task و Activity را در یک Timeline نمایش دهد، بدون ادغام مدل/رفتار آن‌ها.
- [ ] سررسید گذشته، کارهای من، مسئول، وضعیت و اولویت فیلتر شوند.
- [ ] تکمیل Task به‌تنهایی وضعیت Opportunity/Order/Production را تغییر ندهد.
- [ ] یادآوری و Escalation توسط M11 و با رعایت ترجیحات/مجوزها انجام شود.

## تست و وضعیت اجرا

فایل‌های تست مشاهده‌شده:
- `tests/Feature/TaskIsolationTest.php`
- `tests/Feature/TaskTeamAccessTest.php`
- `tests/Feature/AttachmentIsolationTest.php`
- `tests/Feature/AttachmentPreviewTest.php`

پوشش مشاهده‌شده شامل جداسازی شرکت، لینک Opportunity، مشاهده مدیر مستقیم/غیرمستقیم، منع ارجاع به عضو شرکت دیگر، Audit update برای Task/Activity، idempotency در اجرای ترتیبی، پیوست خصوصی، جلوگیری از دانلود بین‌شرکتی و Preview محدود به تصویر است. تست‌ها در این بازنگری اجرا نشده‌اند. تست لینک والد بین‌شرکتی/نوع غیرمجاز، رقابت هم‌زمان idempotency، Transition مجاز، حذف Audit، API/Job و درخت مدیریت در تغییرات عضویت در مجموعه منتخب مشاهده نشد.

نتیجه تست اجرایی برای hamarehOffice وجود ندارد؛ پروژه جدید هنوز scaffold نشده است.

## تصمیم‌ها و کارهای باز

1. باگ مرزی linkable بین شرکت‌ها و allowlist Morph پیش از پیاده‌سازی جدید به‌عنوان الزام امنیتی ثبت شود.
2. قرارداد M01 برای سلسله‌مراتب مدیر، مجوزهای تیمی و shortcut مدیر شرکت تثبیت شود.
3. Task Transition به Actionهای صریح منتقل شود و تغییر status از SaveTask عمومی حذف گردد.
4. idempotency در رقابت هم‌زمان با بازیابی duplicate-key تضمین شود.
5. Audit تمام تغییرات/حذف‌ها و مسیرهای Job/API تضمین شود.
6. سیاست سازگاری Activity::TYPE_TASK قدیمی با Task مستقل تعیین شود.
7. پیوست‌ها از M00 و یادآوری‌ها از M11 استفاده کنند.
8. تست‌های موجود در محیط قابل تکرار اجرا و تست‌های منفی/هم‌زمانی/کانال‌های مختلف افزوده شوند.

**جمع‌بندی:** کد قدیمی برای Task پایه، ارجاع درون‌شرکتی، مشاهده سلسله‌مراتبی مدیر، Audit و پیوست خصوصی قابلیت‌های قابل توجهی دارد و تست‌های Feature هدفمند نیز موجودند. بااین‌حال اعتبارسنجی مالکیت موجودیت polymorphic، Transition مستقل وضعیت، همسانی Policy و Action، و idempotency در رقابت هم‌زمان شکاف‌های مهم‌اند. پروژه جدید باید این مرزهای امنیتی و دامنه‌ای را پیش از ساخت میزکار Vue تثبیت کند.
