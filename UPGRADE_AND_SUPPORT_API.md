# راهنمای API مدیریت، ارتقای حساب، پشتیبانی و کلاس آموزشی

تمام مسیرهای این سند زیر پیشوند `/api` قرار دارند و به توکن Sanctum نیاز دارند:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

## نقش‌های سیستم

- `user`: کاربر عادی؛ رزرو سالن و ثبت‌نام کلاس
- `venue_admin`: سالن‌دار؛ مدیریت سالن و امکان ساخت کلاس آموزشی
- `instructor`: مربی؛ ساخت و مدیریت کلاس آموزشی بدون نیاز به داشتن سالن
- `super_admin`: مدیریت کاربران، درخواست‌های ارتقا، پشتیبانی و کلاس‌ها

## جریان ارتقا به سالن‌دار یا مربی

کاربر با نقش `user` درخواست ارتقا به `venue_admin` یا `instructor` ثبت می‌کند. تا وقتی درخواست در وضعیت `pending` است امکان ثبت درخواست دوم ندارد. ادمین درخواست را تأیید یا رد می‌کند و در صورت تأیید، نقش درخواستی روی حساب کاربر قرار می‌گیرد.

ثبت‌نام عمومی همیشه نقش `user` می‌سازد. ارسال `role` در endpoint ثبت‌نام باعث ساخت حساب ادمین یا سالن‌دار نمی‌شود.

### ثبت درخواست ارتقا

```http
POST /api/upgrade-requests
```

```json
{
  "requested_role": "venue_admin",
  "business_name": "مجموعه ورزشی نمونه",
  "phone": "09121234567",
  "reason": "مالک مجموعه هستم و قصد ثبت سالن را دارم."
}
```

برای درخواست مربی شدن:

```json
{
  "requested_role": "instructor",
  "phone": "09121234567",
  "reason": "مربی تنیس هستم و قصد برگزاری کلاس دارم."
}
```

`business_name` فقط برای درخواست `venue_admin` اجباری است.

پاسخ موفق `201` و وضعیت اولیه درخواست `pending` است. اگر درخواست در انتظار دیگری وجود داشته باشد پاسخ `409` برمی‌گردد.

### سوابق درخواست‌های کاربر

```http
GET /api/upgrade-requests
```

خروجی صفحه‌بندی‌شده است و جدیدترین درخواست ابتدا نمایش داده می‌شود.

### فهرست درخواست‌ها برای ادمین

فقط نقش `super_admin`:

```http
GET /api/admin/upgrade-requests
GET /api/admin/upgrade-requests?status=pending
```

مقادیر فیلتر وضعیت: `pending`، `approved` و `rejected`.

### تأیید یا رد درخواست

```http
PATCH /api/admin/upgrade-requests/{request_id}
```

تأیید:

```json
{
  "status": "approved",
  "admin_note": "اطلاعات بررسی و تأیید شد."
}
```

رد:

```json
{
  "status": "rejected",
  "admin_note": "مدارک مالکیت کامل نیست."
}
```

درخواست بررسی‌شده دوباره قابل بررسی نیست و پاسخ `409` می‌دهد. با تأیید درخواست، نقش کاربر در همان تراکنش دیتابیس به مقدار `requested_role` تغییر می‌کند.

## سیستم تیکت پشتیبانی

کاربر فقط تیکت‌های خودش را می‌بیند. `super_admin` به همه تیکت‌ها دسترسی دارد و می‌تواند پاسخ بدهد یا وضعیت را تغییر دهد.

### ایجاد تیکت

```http
POST /api/support/tickets
```

```json
{
  "subject": "مشکل در پرداخت",
  "message": "پرداخت انجام شده ولی نتیجه رزرو را دریافت نکردم.",
  "category": "payment",
  "priority": "high"
}
```

دسته‌بندی‌های مجاز:

- `general`
- `payment`
- `reservation`
- `venue`
- `account`

اولویت‌های مجاز: `low`، `normal` و `high`. پاسخ موفق `201` است و شماره‌ای مانند `TKT-AB12CD34EF` برمی‌گرداند.

### فهرست و جزئیات تیکت‌های کاربر

```http
GET /api/support/tickets
GET /api/support/tickets/{ticket_number}
```

### ارسال پیام توسط کاربر

```http
POST /api/support/tickets/{ticket_number}/messages
```

```json
{
  "message": "تصویر رسید را هم در اختیار پشتیبانی قرار می‌دهم."
}
```

روی تیکت بسته‌شده نمی‌توان پیام جدید فرستاد و پاسخ `409` برمی‌گردد.

### مدیریت تیکت‌ها توسط ادمین

فقط نقش `super_admin`:

```http
GET /api/admin/support/tickets
GET /api/admin/support/tickets?status=open
GET /api/admin/support/tickets/{ticket_number}
POST /api/admin/support/tickets/{ticket_number}/messages
PATCH /api/admin/support/tickets/{ticket_number}/status
```

نمونه پاسخ ادمین:

```json
{
  "message": "پرداخت شما در حال بررسی است."
}
```

نمونه تغییر وضعیت:

```json
{
  "status": "closed"
}
```

وضعیت‌های مجاز تیکت:

- `open`: تیکت تازه یا باز
- `answered`: پشتیبانی پاسخ داده است
- `waiting_for_user`: کاربر پیام جدید فرستاده و منتظر پشتیبانی است
- `closed`: تیکت بسته شده است

## راه‌اندازی

پس از دریافت تغییرات migrationها را اجرا کنید:

```bash
php artisan migrate
php artisan config:clear
```

برای ساخت اولین `super_admin` از seeder، console یا تغییر کنترل‌شده مستقیم در دیتابیس استفاده کنید؛ endpoint ثبت‌نام عمومی عمداً اجازه تعیین نقش مدیریتی نمی‌دهد.

## کلاس‌های آموزشی

### ساختار کلاس

هر کلاس شامل عنوان، توضیحات، رشته یا دسته‌بندی، سطح، ظرفیت، قیمت، روزها و ساعت‌ها، تاریخ شروع و پایان، ویژگی‌ها و محل برگزاری است. اتصال کلاس به سالن اختیاری است:

- مربی بدون سالن، `location` را ارسال می‌کند و `venue_id` را خالی می‌گذارد.
- سالن‌دار می‌تواند شناسه یکی از سالن‌های متعلق به خودش را در `venue_id` قرار دهد.
- مربی یا سالن‌دار نمی‌تواند کلاس را به سالن شخص دیگری متصل کند.

وضعیت‌های کلاس:

- `draft`: پیش‌نویس و غیرقابل مشاهده برای عموم
- `published`: منتشرشده و قابل ثبت‌نام
- `cancelled`: لغوشده

سطح‌های مجاز: `beginner`، `intermediate`، `advanced` و `all`.

### فهرست عمومی کلاس‌ها

بدون نیاز به توکن:

```http
GET /api/educational-classes
```

فیلترهای قابل استفاده:

```http
GET /api/educational-classes?search=تنیس&category=tennis&level=beginner&instructor_id=12&from_date=2026-09-01&max_price=1000000
```

فقط کلاس‌های `published` نمایش داده می‌شوند. هر آیتم شامل `active_enrollments_count` و `available_capacity` است.

### جزئیات عمومی کلاس

```http
GET /api/educational-classes/{class_slug}
```

نمونه بخش‌های مهم پاسخ:

```json
{
  "id": 5,
  "title": "دوره مقدماتی تنیس",
  "slug": "دوره-مقدماتی-تنیس-a1b2c3",
  "description": "آموزش تنیس از پایه",
  "category": "tennis",
  "level": "beginner",
  "capacity": 12,
  "available_capacity": 8,
  "price": "500000.00",
  "location": "مجموعه ورزشی آزادی",
  "schedule": [
    { "day": "شنبه", "start_time": "10:00", "end_time": "11:30" },
    { "day": "دوشنبه", "start_time": "10:00", "end_time": "11:30" }
  ],
  "features": ["ارائه گواهی", "تجهیزات تمرینی"],
  "registration_deadline": "2026-08-30T20:29:00.000000Z",
  "starts_on": "2026-09-01",
  "ends_on": "2026-10-01",
  "instructor": { "id": 12, "name": "مربی نمونه" },
  "venue": null
}
```

### ثبت‌نام کاربر در کلاس

نیازمند توکن:

```http
POST /api/educational-classes/{class_slug}/enroll
```

بدنه لازم نیست. پاسخ `201` شامل فاکتور، مبلغ و لینک پرداخت است. پس از پرداخت موفق، همین مبلغ داخل enrollment ذخیره می‌شود و تغییر بعدی قیمت کلاس روی ثبت‌نام قبلی اثر ندارد.

پاسخ در این مرحله فاکتور پرداخت است و هنوز enrollment ساخته نشده:

```json
{
  "invoice": "77a8ea39-375b-4c49-8db8-511fb1e999ea",
  "amount": "2800000.00",
  "payment_url": "http://localhost:2000/api/v1/payments/boometo/redirect/77a8ea39-375b-4c49-8db8-511fb1e999ea",
  "class": {
    "id": 4,
    "slug": "futsal-kids-seed",
    "title": "مدرسه فوتسال کودکان"
  }
}
```

فرانت باید کاربر را با `window.location.href = payment_url` به درگاه بفرستد. پرداخت‌های pending و منقضی‌نشده موقتاً در محاسبه ظرفیت لحاظ می‌شوند تا چند کاربر هم‌زمان بیشتر از ظرفیت وارد پرداخت نشوند. فقط بعد از verify موفق، enrollment با `payment_status=paid` ساخته می‌شود.

خطاهای رایج:

- ظرفیت تکمیل، شروع کلاس یا عبور از مهلت ثبت‌نام: `409`
- ثبت‌نام تکراری: `409`
- کلاس پیش‌نویس یا لغوشده: `409`

بعد از پرداخت موفق، callback فرانت شامل `status=true`، مقدار `type=educational_class`، شناسه enrollment و slug کلاس است. جزئیات کامل در فایل `PAYMENT_API.md` آمده است.

### کلاس‌های ثبت‌نام‌شده کاربر

```http
GET /api/my/educational-class-enrollments
```

### لغو ثبت‌نام

```http
DELETE /api/educational-classes/{class_slug}/enroll
```

چون ثبت‌نام کلاس فقط پس از پرداخت ساخته می‌شود، لغو آن نیازمند هماهنگی بازپرداخت است و در وضعیت پرداخت‌شده پاسخ `409` می‌گیرد؛ کاربر باید از مسیر پشتیبانی درخواست لغو و استرداد ثبت کند.

## پنل مربی و سالن‌دار

این مسیرها فقط برای `instructor`، `venue_admin` و `super_admin` هستند.

### فهرست کلاس‌های ساخته‌شده

```http
GET /api/manage/educational-classes
```

### ایجاد کلاس

```http
POST /api/manage/educational-classes
```

```json
{
  "title": "دوره مقدماتی تنیس",
  "description": "آموزش کامل تنیس از پایه",
  "category": "tennis",
  "level": "beginner",
  "capacity": 12,
  "price": 500000,
  "venue_id": null,
  "location": "مجموعه ورزشی آزادی",
  "schedule": [
    { "day": "شنبه", "start_time": "10:00", "end_time": "11:30" },
    { "day": "دوشنبه", "start_time": "10:00", "end_time": "11:30" }
  ],
  "features": ["ارائه گواهی", "تجهیزات تمرینی"],
  "registration_deadline": "2026-08-30 23:59:00",
  "starts_on": "2026-09-01",
  "ends_on": "2026-10-01",
  "status": "published"
}
```

برای ذخیره موقت، `status` را `draft` بفرستید.

### ویرایش و حذف کلاس

```http
PUT /api/manage/educational-classes/{class_slug}
DELETE /api/manage/educational-classes/{class_slug}
```

در ویرایش فقط فیلدهای تغییریافته قابل ارسال هستند. کلاسی که ثبت‌نام فعال دارد حذف نمی‌شود و بهتر است وضعیت آن به `cancelled` تغییر کند.

### مشاهده ثبت‌نام‌کنندگان یک کلاس

```http
GET /api/manage/educational-classes/{class_slug}/enrollments
```

این مسیر نام، ایمیل، مبلغ ثبت‌شده، وضعیت ثبت‌نام و وضعیت پرداخت کاربران را برمی‌گرداند.

### گزارش عملکرد کلاس‌ها

```http
GET /api/manage/educational-classes/analytics
```

ساختار پاسخ:

```json
{
  "summary": {
    "classes_count": 4,
    "published_classes_count": 3,
    "total_registrations": 31,
    "registered_value": 15500000,
    "paid_revenue": 9000000
  },
  "best_class": {},
  "classes": []
}
```

- `best_class`: کلاسی که بیشترین ثبت‌نام فعال را دارد.
- `registered_value`: ارزش کل ثبت‌نام‌های فعال، چه پرداخت‌شده و چه پرداخت‌نشده.
- `paid_revenue`: فقط درآمد ثبت‌نام‌های دارای `payment_status=paid`.
- آرایه `classes` براساس تعداد ثبت‌نام مرتب است و برای نمودار مقایسه عملکرد کلاس‌ها قابل استفاده است.

## مسیرهای پنل ادمین

تمام مسیرهای این بخش نیازمند نقش `super_admin` هستند، مگر جایی که صریحاً نقش سالن‌دار نیز ذکر شده باشد.

### کاربران

```http
GET /api/admin/users
GET /api/admin/users?role=instructor&search=علی
GET /api/admin/users/{user_id}
PATCH /api/admin/users/{user_id}/role
```

نمونه تغییر نقش:

```json
{ "role": "instructor" }
```

نقش‌های مجاز: `user`، `venue_admin`، `instructor` و `super_admin`. ادمین نمی‌تواند نقش مدیریتی خودش را حذف کند.

### درخواست‌های ارتقا

```http
GET /api/admin/upgrade-requests
GET /api/admin/upgrade-requests?status=pending
PATCH /api/admin/upgrade-requests/{request_id}
```

قابلیت‌ها: مشاهده مشخصات متقاضی، فیلتر وضعیت، تأیید یا رد، ثبت یادداشت ادمین و اعمال خودکار نقش درخواستی.

### مدیریت پشتیبانی

```http
GET /api/admin/support/tickets
GET /api/admin/support/tickets?status=open
GET /api/admin/support/tickets/{ticket_number}
POST /api/admin/support/tickets/{ticket_number}/messages
PATCH /api/admin/support/tickets/{ticket_number}/status
```

قابلیت‌ها: مشاهده همه تیکت‌ها، فیلتر وضعیت، مشاهده مکالمه، پاسخ‌گویی و بستن یا بازکردن تیکت.

### مدیریت کلاس‌های آموزشی

```http
GET /api/admin/educational-classes
GET /api/admin/educational-classes?status=published
PATCH /api/admin/educational-classes/{class_slug}/status
```

نمونه تغییر وضعیت:

```json
{ "status": "cancelled" }
```

ادمین می‌تواند همه کلاس‌ها و تعداد ثبت‌نام هر کلاس را ببیند و وضعیت را به `draft`، `published` یا `cancelled` تغییر دهد.

### سالن‌ها و رزروها

مسیرهای زیر برای `venue_admin` و `super_admin` هستند:

```http
GET /api/venues/manage/admin
GET /api/venues/manage/admin/{venue_id}
POST /api/admin/venues
POST /api/venues/admin/manage/update/{venue_id}
POST /api/venues/upload/{venue_id}
POST /api/venues/price/{venue_id}
GET /api/admin/dashboard
PATCH /api/reservations/{reservation_id}/status
```

قابلیت‌ها: لیست سالن‌های تحت مدیریت، جزئیات سالن، ساخت و ویرایش سالن، آپلود تصویر، قیمت‌گذاری سانس‌ها، داشبورد رزرو و تغییر وضعیت رزرو. سالن‌دار فقط مجاز به تغییر سالن‌های متعلق به خودش است؛ `super_admin` به همه سالن‌ها دسترسی دارد.

## درآمد و درخواست برداشت وجه

این بخش برای نقش‌های `instructor` و `venue_admin` در دسترس است.

منابع درآمد به این شکل محاسبه می‌شوند:

- درآمد مربی یا برگزارکننده: ثبت‌نام‌های کلاس متعلق به او که `payment_status=paid` دارند.
- درآمد سالن‌دار: فاکتورهای پرداخت‌شده رزرو سالن‌های متعلق به او.
- ثبت‌نام یا پرداخت `unpaid` جزو موجودی قابل برداشت نیست.
- درخواست‌های برداشت `pending`، `approved` و `paid` از درآمد کل کسر می‌شوند.
- درخواست `rejected` مبلغ را آزاد می‌کند و دوباره قابل برداشت خواهد بود.

در حال حاضر مبلغ‌ها به‌صورت درآمد ناخالص محاسبه می‌شوند و کارمزد پلتفرم از آن‌ها کم نمی‌شود.

### مشاهده موجودی درآمد

```http
GET /api/earnings/balance
```

نمونه پاسخ:

```json
{
  "venue_revenue": 12000000,
  "class_revenue": 8000000,
  "total_revenue": 20000000,
  "reserved_or_withdrawn": 5000000,
  "available_to_withdraw": 15000000
}
```

- `venue_revenue`: درآمد پرداخت‌شده رزرو سالن‌ها
- `class_revenue`: درآمد پرداخت‌شده کلاس‌ها
- `reserved_or_withdrawn`: مجموع درخواست‌های در انتظار، تأییدشده و پرداخت‌شده
- `available_to_withdraw`: حداکثر مبلغی که اکنون می‌توان درخواست کرد

### ثبت درخواست برداشت

```http
POST /api/withdrawals
```

```json
{
  "amount": 5000000,
  "iban": "IR123456789012345678901234",
  "account_holder": "علی رضایی"
}
```

نکات اعتبارسنجی:

- `amount` باید مثبت و کمتر یا مساوی `available_to_withdraw` باشد.
- `iban` باید شبای ایران، شامل `IR` و ۲۴ رقم باشد.
- فاصله‌های شماره شبا در سرور حذف می‌شوند.
- اگر مبلغ از موجودی آزاد بیشتر باشد پاسخ `422` برمی‌گردد.
- درخواست موفق با وضعیت `pending` و شماره‌ای مانند `WDR-AB12CD34EF56` ساخته می‌شود.

### سوابق برداشت برگزارکننده یا سالن‌دار

```http
GET /api/withdrawals
```

خروجی صفحه‌بندی‌شده و شامل مبلغ، شبا، صاحب حساب، وضعیت، یادداشت ادمین و تاریخ پرداخت است.

وضعیت‌های درخواست برداشت:

- `pending`: در انتظار بررسی ادمین
- `approved`: تأییدشده و در انتظار انتقال بانکی
- `rejected`: ردشده؛ مبلغ دوباره آزاد می‌شود
- `paid`: وجه منتقل شده است

### مدیریت برداشت‌ها توسط ادمین

فقط نقش `super_admin`:

```http
GET /api/admin/withdrawals
GET /api/admin/withdrawals?status=pending
GET /api/admin/withdrawals/{withdrawal_number}
PATCH /api/admin/withdrawals/{withdrawal_number}/status
```

جزئیات درخواست علاوه بر مشخصات کاربر، موجودی فعلی او را هم نمایش می‌دهد.

تأیید درخواست:

```json
{
  "status": "approved",
  "admin_note": "اطلاعات حساب تأیید شد."
}
```

ثبت انتقال موفق:

```json
{
  "status": "paid",
  "admin_note": "وجه با شماره پیگیری بانکی منتقل شد."
}
```

رد درخواست:

```json
{
  "status": "rejected",
  "admin_note": "نام صاحب حساب با اطلاعات کاربر مطابقت ندارد."
}
```

ترتیب تغییر وضعیت کنترل می‌شود:

- `pending` فقط به `approved` یا `rejected` تغییر می‌کند.
- `approved` فقط به `paid` یا `rejected` تغییر می‌کند.
- درخواست `paid` یا `rejected` نهایی است و دوباره تغییر نمی‌کند.

## داده‌های نمونه و حساب‌های تست

برای ساخت کاربران، سالن‌ها، سانس‌ها و کلاس‌های نمونه اجرا کنید:

```bash
php artisan db:seed
```

Seeder قابل اجرای مجدد است و با اجرای دوباره داده تکراری نمی‌سازد. داده‌های ساخته‌شده شامل ۱۲ سالن (دو سالن از هر نوع)، سانس‌های ۷ روز آینده و ۴ کلاس آموزشی منتشرشده است.

حساب‌های نمونه:

| نقش | ایمیل | رمز عبور |
|---|---|---|
| ادمین کل | `admin@reservation.test` | `Admin@123456` |
| سالن‌دار | `venue@reservation.test` | `Venue@123456` |
| مربی تنیس | `coach@reservation.test` | `Coach@123456` |
| مربی بدنسازی | `fitness@reservation.test` | `Coach@123456` |
| کاربر عادی | `user@reservation.test` | `User@123456` |

در محیط توسعه، برای بازسازی کامل دیتابیس همراه داده‌های نمونه می‌توان از دستور زیر استفاده کرد. این دستور تمام داده‌های فعلی دیتابیس را حذف می‌کند:

```bash
php artisan migrate:fresh --seed
```
