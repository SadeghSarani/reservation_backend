# راهنمای فرانت‌اند بخش «تجربه‌ها»

این بخش برای ایونت‌هایی مثل «کارگاه ساخت ماگ سفالی»، تور غذا، تجربه عکاسی یا کمپ چندروزه است. تجربه می‌تواند یک‌روزه یا چندروزه باشد، آدرس مستقل داشته باشد و توسط کاربر دارای نقش `instructor` یا `venue_admin` برگزار شود.

## مدل مفهومی

- `starts_at` و `ends_at` بازه کلی تجربه را مشخص می‌کنند.
- `duration_days` توسط بک‌اند محاسبه می‌شود؛ مقدار `1` یعنی یک‌روزه و بیشتر از آن یعنی چندروزه.
- `schedule` برنامه اختیاری هر روز است. برای تجربه چندروزه بهتر است برگزارکننده برنامه تمام روزها را وارد کند.
- `price` قیمت هر نفر است و کاربر با `quantity` می‌تواند برای چند نفر رزرو کند.
- حداقل یکی از `address` یا موقعیت نقشه نداریم: `address` همیشه اجباری است و مختصات اختیاری‌اند.
- زمان‌ها ISO 8601 هستند. فرانت آن‌ها را با منطقه زمانی کاربر نمایش دهد و مقدار دریافتی از فرم را با offset ارسال کند.

## TypeScript

```ts
export type ExperienceStatus = "draft" | "published" | "cancelled";
export type BookingStatus = "booked" | "cancelled";
export type PaymentStatus = "unpaid" | "paid" | "refunded";
export type Money = string | number;

export interface ExperienceScheduleItem {
  date: string;       // YYYY-MM-DD
  start_time: string; // HH:mm
  end_time: string;   // HH:mm
}

export interface ExperienceOrganizer {
  id: number;
  name: string;
  email?: string;
}

export interface Experience {
  id: number;
  organizer_id: number;
  title: string;
  slug: string;
  description: string;
  category: string | null;
  capacity: number;
  available_capacity: number;
  price: Money; // قیمت یک نفر
  address: string;
  city: string | null;
  latitude: string | number | null;
  longitude: string | number | null;
  starts_at: string;
  ends_at: string;
  duration_days: number;
  schedule: ExperienceScheduleItem[] | null;
  includes: string[] | null;
  requirements: string[] | null;
  booking_deadline: string | null;
  status: ExperienceStatus;
  organizer?: ExperienceOrganizer;
  created_at: string;
  updated_at: string;
}

export interface ExperienceBooking {
  id: number;
  experience_id: number;
  user_id: number;
  quantity: number;
  unit_price: Money;
  total_price: Money;
  status: BookingStatus;
  payment_status: PaymentStatus;
  booked_at: string;
  cancelled_at: string | null;
  experience?: Experience;
}
```

## صفحات عمومی

### لیست تجربه‌ها

```http
GET /api/experiences
```

خروجی `Paginated<Experience>` و تعداد هر صفحه ۲۰ است.

فیلترها:

```http
GET /api/experiences?search=سفال&category=arts-crafts&city=تهران&from_date=2027-09-01&to_date=2027-09-30&max_price=1000000&page=1
```

پیشنهاد کارت:

- عنوان و دسته‌بندی
- نام برگزارکننده
- تاریخ شروع
- برچسب `یک‌روزه` یا `${duration_days} روزه`
- شهر
- ظرفیت باقی‌مانده
- قیمت «برای هر نفر»
- وضعیت تکمیل ظرفیت

### جزئیات تجربه

```http
GET /api/experiences/{slug}
```

در صفحه جزئیات نمایش داده شود:

- توضیحات، برگزارکننده، شروع و پایان
- برنامه روزها (`schedule`)
- آدرس و در صورت وجود مختصات، نقشه
- «شامل چه چیزهایی است» (`includes`)
- «چه چیزهایی همراه بیاورم / محدودیت‌ها» (`requirements`)
- ظرفیت باقی‌مانده و انتخاب تعداد نفر

منطق دکمه:

```ts
const bookingOpen =
  experience.status === "published" &&
  experience.available_capacity > 0 &&
  new Date(experience.starts_at) > new Date() &&
  (!experience.booking_deadline ||
    new Date(experience.booking_deadline) > new Date());
```

تصمیم نهایی ظرفیت و مهلت همیشه با بک‌اند است.

## رزرو و پرداخت

نیازمند Bearer Token:

```http
POST /api/experiences/{slug}/book
Authorization: Bearer <token>
Content-Type: application/json

{
  "quantity": 2
}
```

`quantity` عدد صحیح بین ۱ تا ۲۰ است و نباید از `available_capacity` بیشتر باشد.

پاسخ موفق `201`:

```json
{
  "invoice": "77a8ea39-375b-4c49-8db8-511fb1e999ea",
  "amount": "1500000.00",
  "quantity": 2,
  "payment_url": "http://localhost:2000/api/v1/payments/boometo/redirect/...",
  "experience": {
    "id": 10,
    "slug": "ceramic-mug-workshop-a1b2c3",
    "title": "کارگاه ساخت ماگ سفالی"
  }
}
```

سپس:

```ts
const { data } = await api.post(`/experiences/${slug}/book`, { quantity });
window.location.assign(data.payment_url);
```

تا ۲۰ دقیقه ظرفیت انتخاب‌شده برای فاکتور pending نگه داشته می‌شود.

### callback پرداخت

موفق:

```text
/payment/callback
?status=true
&type=experience
&invoice=...
&reference=...
&booking_id=25
&experience=ceramic-mug-workshop-a1b2c3
```

ناموفق:

```text
/payment/callback?status=false&invoice=...&message=...
```

فرانت تنها وقتی `status=true&type=experience` است پیام موفقیت نمایش دهد. تأیید واقعی پرداخت server-to-server انجام می‌شود.

### تجربه‌های رزروشده من

```http
GET /api/my/experience-bookings
Authorization: Bearer <token>
```

خروجی `Paginated<ExperienceBooking>` همراه `experience.organizer` است.

لغو رزرو پرداخت‌شده مستقیماً مجاز نیست و باید از تیکت پشتیبانی پیگیری شود:

```http
DELETE /api/experiences/{slug}/book
```

## پنل برگزارکننده

نقش‌های مجاز: `instructor`، `venue_admin` و `super_admin`.

```http
GET    /api/manage/experiences
POST   /api/manage/experiences
PUT    /api/manage/experiences/{slug}
DELETE /api/manage/experiences/{slug}
GET    /api/manage/experiences/{slug}/bookings
```

نمونه ساخت:

```json
{
  "title": "کارگاه ساخت ماگ سفالی",
  "description": "در این کارگاه ماگ خودتان را می‌سازید و لعاب می‌زنید.",
  "category": "arts-crafts",
  "capacity": 12,
  "price": 750000,
  "address": "تهران، خیابان نمونه، پلاک ۱۲",
  "city": "تهران",
  "latitude": 35.7219,
  "longitude": 51.3347,
  "starts_at": "2027-09-01T09:00:00+03:30",
  "ends_at": "2027-09-02T13:00:00+03:30",
  "schedule": [
    { "date": "2027-09-01", "start_time": "09:00", "end_time": "13:00" },
    { "date": "2027-09-02", "start_time": "09:00", "end_time": "13:00" }
  ],
  "includes": ["گل و ابزار", "لعاب", "پذیرایی"],
  "requirements": ["مناسب افراد بالای ۱۶ سال"],
  "booking_deadline": "2027-08-30T23:59:00+03:30",
  "status": "draft"
}
```

فرم پیشنهادی برگزارکننده:

1. اطلاعات اصلی: عنوان، توضیح، دسته‌بندی و قیمت هر نفر
2. زمان: شروع، پایان و برنامه اختیاری روزها
3. مکان: شهر، آدرس و انتخاب اختیاری نقطه روی نقشه
4. جزئیات: امکانات شامل‌شده و الزامات
5. فروش: ظرفیت، مهلت رزرو و وضعیت انتشار

برای جلوگیری از انتشار ناخواسته، مقدار اولیه `status` در فرانت `draft` باشد. کاهش ظرفیت به کمتر از تعداد صندلی‌های رزروشده با `409` رد می‌شود و تجربه دارای رزرو فعال نیز قابل حذف نیست؛ در این حالت وضعیت را `cancelled` کنید.

## مدیریت سوپرادمین

```http
GET   /api/admin/experiences?status=published
PATCH /api/admin/experiences/{slug}/status

{ "status": "cancelled" }
```

## خطاهای مهم

- `401`: کاربر وارد نشده؛ مسیر ورود و سپس بازگشت به تجربه
- `403`: نقش یا مالکیت کافی نیست
- `404`: تجربه منتشر نیست یا وجود ندارد
- `409`: تکمیل ظرفیت، پایان مهلت، شروع تجربه، رزرو تکراری یا پرداخت pending
- `422`: خطای فرم؛ خطاهای Laravel را کنار هر فیلد نشان دهید

مبلغ را با `Intl.NumberFormat("fa-IR")` نمایش دهید، اما برای محاسبه نهایی به مبلغ فرانت اعتماد نکنید؛ مبلغ فاکتور را بک‌اند از قیمت ذخیره‌شده تجربه محاسبه می‌کند.
