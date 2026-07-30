# راهنمای فرانت ثبت‌نام و پرداخت کلاس آموزشی

این سند جریان کامل مشاهده کلاس، ثبت‌نام، انتقال به درگاه و دریافت نتیجه پرداخت را توضیح می‌دهد.

## تنظیمات بک‌اند

در فایل `.env` بک‌اند:

```env
APP_URL=http://localhost:2000
FRONTEND_URL=http://localhost:3000
BOOMETO_FRONTEND_CALLBACK_URL=http://localhost:3000/payment/callback

BOOMETO_URL=https://payment-provider.example
BOOMETO_TOKEN=your-token
BOOMETO_AMOUNT_MULTIPLIER=10
BOOMETO_INVOICE_TTL=20
```

بعد از تغییر env:

```bash
php artisan config:clear
```

## ۱. دریافت فهرست کلاس‌ها

این endpoint عمومی است و توکن نمی‌خواهد:

```http
GET /api/educational-classes
```

فیلترهای قابل استفاده:

```http
GET /api/educational-classes?search=فوتسال&category=futsal&level=beginner&from_date=2026-08-01&max_price=5000000
```

هر کلاس شامل `capacity`، تعداد `active_enrollments_count` و مقدار `available_capacity` است.

## ۲. دریافت جزئیات کلاس

```http
GET /api/educational-classes/{class_slug}
```

مثال:

```http
GET /api/educational-classes/futsal-kids-seed
```

نمونه پاسخ:

```json
{
  "id": 4,
  "title": "مدرسه فوتسال کودکان",
  "slug": "futsal-kids-seed",
  "description": "آموزش مهارت‌های پایه فوتسال برای کودکان ۸ تا ۱۲ سال.",
  "category": "futsal",
  "level": "beginner",
  "capacity": 20,
  "available_capacity": 14,
  "price": "2800000.00",
  "schedule": [
    {
      "day": "پنجشنبه",
      "start_time": "15:00",
      "end_time": "16:30"
    }
  ],
  "features": ["۸ جلسه", "لباس تمرین", "گزارش پیشرفت"],
  "registration_deadline": "2026-08-30T20:29:00.000000Z",
  "starts_on": "2026-09-01",
  "ends_on": "2026-10-01",
  "status": "published"
}
```

فرانت باید قبل از نمایش دکمه ثبت‌نام بررسی کند:

```js
const canEnroll = educationalClass.available_capacity > 0;
```

تصمیم نهایی ظرفیت همیشه در بک‌اند گرفته می‌شود.

## ۳. شروع ثبت‌نام و ساخت فاکتور

این endpoint به توکن Sanctum نیاز دارد:

```http
POST /api/educational-classes/{class_slug}/enroll
Authorization: Bearer USER_TOKEN
Accept: application/json
```

مثال:

```http
POST http://localhost:2000/api/educational-classes/futsal-kids-seed/enroll
```

بدنه درخواست خالی است.

نمونه پاسخ موفق `201`:

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

در این مرحله هنوز enrollment ساخته نشده است.

## ۴. انتقال کاربر به درگاه

بعد از دریافت پاسخ، مرورگر را به `payment_url` منتقل کنید:

```js
async function enrollInClass(classSlug) {
  const response = await api.post(
    `/educational-classes/${classSlug}/enroll`
  );

  window.location.href = response.data.payment_url;
}
```

پرداخت pending تا زمان انقضای فاکتور یک جای ظرفیت کلاس را نگه می‌دارد. مقدار پیش‌فرض این زمان ۲۰ دقیقه است و با `BOOMETO_INVOICE_TTL` تنظیم می‌شود.

## ۵. بازگشت موفق از درگاه

پس از verify موفق، بک‌اند enrollment را با وضعیت‌های زیر می‌سازد:

```json
{
  "status": "registered",
  "payment_status": "paid"
}
```

سپس مرورگر به صفحه callback فرانت منتقل می‌شود:

```text
http://localhost:3000/payment/callback
  ?status=true
  &type=educational_class
  &invoice=77a8ea39-375b-4c49-8db8-511fb1e999ea
  &reference=CLASS-REF-123
  &enrollment_id=81
  &class=futsal-kids-seed
```

پارامترها:

- `status=true`: پرداخت تأیید و ثبت‌نام ساخته شده است.
- `type=educational_class`: نوع پرداخت، کلاس آموزشی است.
- `invoice`: شماره عمومی فاکتور
- `reference`: شماره پیگیری درگاه
- `enrollment_id`: شناسه ثبت‌نام ساخته‌شده
- `class`: slug کلاس

## ۶. بازگشت ناموفق

در پرداخت یا verify ناموفق:

```text
http://localhost:3000/payment/callback
  ?status=false
  &invoice=77a8ea39-375b-4c49-8db8-511fb1e999ea
  &message=Payment%20verification%20failed.
```

در این حالت enrollment ساخته نمی‌شود. بعد از منقضی شدن فاکتور pending، ظرفیت نگه‌داری‌شده نیز آزاد می‌شود.

## ۷. پیاده‌سازی صفحه نتیجه فرانت

```js
const params = new URLSearchParams(window.location.search);
const successful = params.get('status') === 'true';
const paymentType = params.get('type');

if (successful && paymentType === 'educational_class') {
  const enrollmentId = params.get('enrollment_id');
  const classSlug = params.get('class');
  const reference = params.get('reference');

  // نمایش «پرداخت موفق و ثبت‌نام کلاس انجام شد»
  // امکان انتقال به جزئیات کلاس یا کلاس‌های من
} else {
  const message = params.get('message');

  // نمایش «پرداخت ناموفق»
  // نمایش دکمه بازگشت به کلاس و تلاش مجدد
}
```

فرانت نباید موفقیت پرداخت را خودش تعیین کند. مقدار `status=true` فقط بعد از استعلام server-to-server بک‌اند از Boomto تولید می‌شود.

## ۸. مشاهده کلاس‌های ثبت‌نام‌شده کاربر

```http
GET /api/my/educational-class-enrollments
Authorization: Bearer USER_TOKEN
```

این endpoint فهرست صفحه‌بندی‌شده enrollmentهای کاربر را همراه اطلاعات کلاس و مربی برمی‌گرداند.

## ۹. خطاهای رایج ثبت‌نام

در موارد زیر endpoint ثبت‌نام پاسخ `409` می‌دهد:

- ظرفیت کلاس تکمیل شده باشد.
- کلاس هنوز منتشر نشده یا لغو شده باشد.
- مهلت ثبت‌نام گذشته باشد.
- کلاس شروع شده باشد.
- کاربر قبلاً ثبت‌نام پرداخت‌شده داشته باشد.
- کاربر یک پرداخت pending و منقضی‌نشده برای همان کلاس داشته باشد.

خطای اعتبارسنجی یا موجود نبودن کلاس می‌تواند پاسخ `404` یا `422` داشته باشد. خطاهای `401` و `403` نیز به معنی نبودن توکن یا نداشتن دسترسی هستند.

## ۱۰. کنترل ظرفیت

ظرفیت قابل استفاده از مجموع موارد زیر محاسبه می‌شود:

```text
ثبت‌نام‌های فعال + فاکتورهای pending و منقضی‌نشده
```

بررسی و ثبت ظرفیت داخل transaction و با قفل دیتابیس انجام می‌شود. بنابراین درخواست‌های هم‌زمان نمی‌توانند تعداد ثبت‌نام‌ها را از ظرفیت کلاس بیشتر کنند.

## ۱۱. لغو ثبت‌نام

```http
DELETE /api/educational-classes/{class_slug}/enroll
Authorization: Bearer USER_TOKEN
```

ثبت‌نام پرداخت‌شده به دلیل نیاز به بازپرداخت مستقیم لغو نمی‌شود و پاسخ `409` می‌گیرد. کاربر باید از سیستم تیکت پشتیبانی درخواست لغو و استرداد وجه ثبت کند.

## خلاصه endpointها

```http
GET    /api/educational-classes
GET    /api/educational-classes/{class_slug}
POST   /api/educational-classes/{class_slug}/enroll
DELETE /api/educational-classes/{class_slug}/enroll
GET    /api/my/educational-class-enrollments

GET    /api/v1/payments/boometo/redirect/{invoice}
GET|POST /api/v1/payments/boometo/callback?invoice={invoice}
```
