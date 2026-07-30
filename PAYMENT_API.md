# راهنمای اتصال فرانت به پرداخت رزرو

## تنظیم آدرس بازگشت فرانت

در فایل `.env` بک‌اند، آدرس صفحه نتیجه پرداخت در فرانت را مشخص کنید:

```env
APP_URL=http://localhost:2000
FRONTEND_URL=http://localhost:3000
BOOMETO_FRONTEND_CALLBACK_URL=http://localhost:3000/payment/callback
```

بعد از تغییر env:

```bash
php artisan config:clear
```

فرانت باید route زیر را داشته باشد:

```text
/payment/callback
```

همین صفحه براساس query parameter به کاربر صفحه موفق یا ناموفق را نمایش می‌دهد.

## شروع رزرو و پرداخت

```http
POST /api/reservations
Authorization: Bearer USER_TOKEN
Accept: application/json
Content-Type: application/json
```

```json
{
  "calendar_interval_id": 15,
  "additionals": [
    { "name": "توپ" }
  ]
}
```

پاسخ موفق `201`:

```json
{
  "invoice": "77a8ea39-375b-4c49-8db8-511fb1e999ea",
  "amount": "1200000.00",
  "payment_url": "http://localhost:2000/api/v1/payments/boometo/redirect/77a8ea39-375b-4c49-8db8-511fb1e999ea"
}
```

بعد از دریافت پاسخ، فرانت باید مرورگر را به `payment_url` منتقل کند:

```js
const response = await api.post('/reservations', payload);
window.location.href = response.data.payment_url;
```

این URL صفحه HTML دریافتی از Boomto را نمایش می‌دهد و کاربر وارد درگاه می‌شود. رزرو هنوز در این مرحله ساخته نشده است.

## بازگشت موفق از درگاه

Boomto ابتدا callback بک‌اند را فراخوانی می‌کند. بک‌اند وضعیت تراکنش را مستقیماً از درگاه verify می‌کند، رزرو را می‌سازد و سپس مرورگر را به آدرس زیر می‌فرستد:

```text
http://localhost:3000/payment/callback?status=true&invoice=77a8ea39-375b-4c49-8db8-511fb1e999ea&reference=REF-123&reservation_id=42
```

پارامترها:

- `status=true`: پرداخت تأیید شده و رزرو ساخته شده است.
- `invoice`: شماره عمومی فاکتور
- `reference`: شماره پیگیری درگاه؛ ممکن است در صورت عدم ارسال درگاه خالی باشد.
- `reservation_id`: شناسه رزرو ساخته‌شده

## بازگشت ناموفق از درگاه

اگر verify ناموفق باشد یا فاکتور دیگر قابل پرداخت نباشد، کاربر به این آدرس برمی‌گردد:

```text
http://localhost:3000/payment/callback?status=false&invoice=77a8ea39-375b-4c49-8db8-511fb1e999ea&message=Payment%20verification%20failed.
```

پارامترها:

- `status=false`: پرداخت یا ساخت رزرو موفق نبوده است.
- `invoice`: شماره فاکتور
- `message`: توضیح خطا برای نمایش مناسب به کاربر

## نمونه صفحه نتیجه در فرانت

```js
const params = new URLSearchParams(window.location.search);
const successful = params.get('status') === 'true';

if (successful) {
  const reservationId = params.get('reservation_id');
  const reference = params.get('reference');
  // نمایش صفحه «پرداخت موفق و رزرو ثبت شد»
} else {
  const message = params.get('message');
  // نمایش صفحه «پرداخت ناموفق» و دکمه تلاش مجدد
}
```

فرانت نباید صرفاً به `status` ارسالی احتمالی خود درگاه اعتماد کند. تصمیم نهایی موفق یا ناموفق بودن در بک‌اند و پس از فراخوانی API وضعیت Boomto انجام می‌شود.

## routeهای بک‌اند

```http
GET  /api/v1/payments/boometo/redirect/{invoice}
GET|POST /api/v1/payments/boometo/callback?invoice={invoice}
```

این دو route عمومی هستند چون مرورگر و درگاه بدون توکن Sanctum آن‌ها را فراخوانی می‌کنند. شماره invoice یک UUID غیرقابل حدس است و تأیید نهایی نیز به‌صورت server-to-server با درگاه انجام می‌شود.

## پرداخت ثبت‌نام کلاس آموزشی

ثبت‌نام کلاس نیز مانند رزرو سالن ابتدا وارد درگاه می‌شود و فقط پس از پرداخت موفق ساخته خواهد شد.

```http
POST /api/educational-classes/{class_slug}/enroll
Authorization: Bearer USER_TOKEN
Accept: application/json
```

مثال:

```http
POST /api/educational-classes/futsal-kids-seed/enroll
```

بدنه درخواست خالی است. پاسخ موفق `201`:

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

هدایت فرانت:

```js
const response = await api.post(`/educational-classes/${classSlug}/enroll`);
window.location.href = response.data.payment_url;
```

تا این لحظه enrollment ساخته نشده است. پرداخت pending تا زمان انقضای فاکتور یک جای ظرفیت را نگه می‌دارد. اگر ظرفیت کلاس با احتساب ثبت‌نام‌ها و پرداخت‌های فعال تکمیل باشد، پاسخ `409` برمی‌گردد.

### بازگشت موفق پرداخت کلاس

```text
http://localhost:3000/payment/callback
  ?status=true
  &type=educational_class
  &invoice=77a8ea39-375b-4c49-8db8-511fb1e999ea
  &reference=CLASS-REF-123
  &enrollment_id=81
  &class=futsal-kids-seed
```

در این وضعیت ثبت‌نام با `status=registered` و `payment_status=paid` ساخته شده است.

### بازگشت ناموفق پرداخت کلاس

```text
http://localhost:3000/payment/callback
  ?status=false
  &invoice=77a8ea39-375b-4c49-8db8-511fb1e999ea
  &message=Payment%20verification%20failed.
```

در پرداخت ناموفق enrollment ساخته نمی‌شود. فاکتور ناموفق یا منقضی‌شده نیز بعد از پایان زمان نگه‌داری، ظرفیتی از کلاس اشغال نمی‌کند.
