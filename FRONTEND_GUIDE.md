# راهنمای پیاده‌سازی فرانت‌اند سامانه رزرو مجموعه‌های ورزشی

> این سند از روی کد فعلی بک‌اند تهیه شده است (Laravel 12 + Sanctum). موارد بخش «مشکلات و ابهام‌های بک‌اند» بخشی از قرارداد قابل اتکا نیستند و بهتر است پیش از نهایی‌کردن فرانت اصلاح شوند.

## 1. معرفی محصول

این پروژه یک سامانه رزرو مجموعه‌های ورزشی است. کاربران می‌توانند مجموعه‌ها را مشاهده کنند، روز و سانس قابل رزرو را انتخاب کنند و رزرو بسازند. مدیر مجموعه، مجموعه‌ها، قیمت سانس‌ها و رزروهای مربوط به مجموعه‌های خودش را مدیریت می‌کند. سوپرادمین به داده‌های همه مجموعه‌ها و رزروها دسترسی دارد.

### نقش‌ها

| نقش | مقدار API | دسترسی اصلی |
|---|---|---|
| کاربر عادی | `user` | مشاهده مجموعه‌ها، ایجاد و مشاهده رزروهای خود |
| مدیر مجموعه | `venue_admin` | مدیریت مجموعه‌های خود، سانس‌ها و رزروهای آن‌ها |
| سوپرادمین | `super_admin` | دسترسی مدیریتی به همه مجموعه‌ها و رزروها |

## 2. تنظیمات اتصال به API

- آدرس Docker فعلی: `http://localhost:2000`
- پیشوند تمام APIها: `/api`
- احراز هویت: Bearer Token با Laravel Sanctum
- هدرهای پیشنهادی:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>
```

برای آپلود تصویر، `Content-Type` را دستی تنظیم نکنید و از `FormData` استفاده کنید تا مرورگر boundary را ایجاد کند.

```ts
import axios from "axios";

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? "http://localhost:2000/api",
  headers: { Accept: "application/json" },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("access_token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```

## 3. مدل‌های داده پیشنهادی برای TypeScript

مقادیر عددی decimal در خروجی Eloquent ممکن است به‌صورت string برگردند؛ برای قیمت‌ها نوع `string | number` در نظر گرفته شده است.

```ts
export type UserRole = "user" | "venue_admin" | "super_admin";
export type VenueType =
  | "gym"
  | "tennis"
  | "football"
  | "basketball"
  | "futsal"
  | "volleyball";
export type BillingType = "hourly" | "monthly";
export type ReservationStatus = "pending" | "confirmed" | "cancelled";
export type Money = string | number;

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  phone?: string | null;
  email_verified_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface AdditionalItem {
  name?: string;
  title?: string;
  price: Money;
  [key: string]: unknown;
}

export interface VenueImage {
  id: number;
  uuid: string;
  pivot?: {
    venue_id: number;
    file_id: number;
  };
}

export interface Venue {
  id: number;
  owner_id: number;
  name: string;
  type: VenueType;
  billing_type: BillingType;
  description: string | null;
  address: string | null;
  capacity: number | null;
  price: Money;
  is_active: boolean | 0 | 1;
  additionals: AdditionalItem[] | null;
  owner?: User;
  images?: VenueImage[];
  venue_price?: VenueTimePrice[];
  created_at: string;
  updated_at: string;
}

export interface CalendarDay {
  id: number;
  day: string | null;
  day_jalali: string | null; // نمونه: 1405/05/01
  holiday: boolean | 0 | 1;
  event: string | null;
  created_at: string;
  updated_at: string;
}

export interface VenueTimePrice {
  id: number;
  venue_id: number;
  calendar_id: number;
  start_time: string; // HH:mm یا HH:mm:ss
  end_time: string;
  price: Money;
  reservation?: Reservation | null;
  created_at: string;
  updated_at: string;
}

export interface Reservation {
  id: number;
  user_id: number;
  venue_id: number;
  calendar_interval_id: number;
  start_at: string;
  end_at: string | null;
  total_price: Money;
  status: ReservationStatus;
  additionals: AdditionalItem[] | null;
  user?: User;
  venue?: Venue;
  created_at: string;
  updated_at: string;
}

export interface PaginationMeta {
  current_page: number;
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: Array<{ url: string | null; label: string; active: boolean }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

export type Paginated<T> = PaginationMeta & { data: T[] };
```

### ساخت URL تصویر

فیلد `images[].uuid` را به مسیر وب زیر بدهید (این مسیر زیر `/api` نیست):

```ts
const imageUrl = `${APP_ORIGIN}/getFile/${image.uuid}`;
const compressedImageUrl = `${imageUrl}?compress=1`;
```

در صورت پیدا نشدن فایل، بک‌اند تلاش می‌کند تصویر پیش‌فرض برگرداند.

## 4. قرارداد احراز هویت

### ثبت‌نام

`POST /api/register` — عمومی

```json
{
  "name": "علی رضایی",
  "email": "ali@example.com",
  "password": "123456",
  "role": "user"
}
```

- `name`: اجباری
- `email`: اجباری، معتبر و یکتا
- `password`: حداقل ۶ کاراکتر
- `role`: اختیاری؛ یکی از سه نقش، با مقدار پیش‌فرض `user`
- پاسخ موفق: `201` با `{ "user": User, "token": "..." }`
- خطای اعتبارسنجی: `422`

نکته امنیتی: در UI عمومی فیلد نقش نمایش داده نشود و همیشه `user` ارسال شود. بک‌اند فعلاً اجازه ثبت‌نام مستقیم `super_admin` را نیز می‌دهد.

### ورود

`POST /api/login` — عمومی

```json
{ "email": "ali@example.com", "password": "123456" }
```

- موفق: `200` با `{ "token": "..." }`
- اطلاعات ورود اشتباه: `400` با پیام فارسی در `message`
- خطای اعتبارسنجی: `422`

پس از ورود، توکن را ذخیره کنید و `GET /api/profile` را برای دریافت کاربر و نقش او صدا بزنید.

### پروفایل و خروج

| متد و مسیر | دسترسی | پاسخ |
|---|---|---|
| `GET /api/profile` | واردشده | `{ "user": User }` |
| `POST /api/logout` | واردشده | پاسخ بدون بدنه |

در فرانت، بعد از logout (حتی اگر پاسخ بک‌اند خطا داشت) توکن و state کاربر پاک شود.

## 5. API مجموعه‌ها

### فهرست عمومی مجموعه‌ها

`GET /api/venues` — عمومی، ولی اگر Bearer Token ارسال شود رفتار براساس نقش تغییر می‌کند.

- بدون توکن یا کاربر عادی: `{ "message": "venues", "data": Paginated<Venue> }`
- مدیر مجموعه: مستقیماً `Paginated<Venue>` و فقط مجموعه‌های خودش
- سوپرادمین: مستقیماً `Paginated<Venue>` و همه مجموعه‌ها
- تعداد هر صفحه: ۱۰
- پارامتر صفحه: `?page=2`
- فیلترها توسط Laravel Purity دریافت می‌شوند؛ نمونه محتمل: `?filters[type]=futsal` و `?filters[is_active]=1`. فهرست مجاز اختصاصی در بک‌اند تعریف نشده است، پس فیلترها باید با API تست شوند.

بهتر است در لایه API پاسخ را normalize کنید:

```ts
export function normalizeVenueList(raw: any): Paginated<Venue> {
  return raw?.data?.current_page ? raw.data : raw;
}
```

### جزئیات مجموعه

`GET /api/venues/{venueId}` — عمومی

خروجی یک `Venue` است و روابط `owner`، `venue_price` و `images` را دارد.

### آمار عمومی نوع مجموعه‌ها

`GET /api/venue/dashboard` — عمومی

```json
{
  "message": "venues",
  "data": [
    { "type": "futsal", "total": 4 },
    { "type": "gym", "total": 2 }
  ]
}
```

### روزهای قابل انتخاب یک مجموعه

`GET /api/venues/calendars/{venueId}` — نیازمند توکن

فقط روزهایی را برمی‌گرداند که برای مجموعه حداقل یک سانس قیمت‌گذاری شده باشد:

```json
{ "message": "calendar", "data": [CalendarDay] }
```

### سانس‌های یک روز

`GET /api/venues/time/{venueId}?calendar_id={calendarId}` — نیازمند توکن

```json
{
  "message": "time",
  "data": [
    {
      "id": 15,
      "venue_id": 2,
      "calendar_id": 70,
      "start_time": "18:00:00",
      "end_time": "19:30:00",
      "price": "500000",
      "reservation": null
    }
  ]
}
```

اگر `reservation` تهی نیست، سانس رزرو شده است و باید غیرفعال نمایش داده شود.

### تمام تقویم

`GET /api/calendar` — نیازمند توکن

پاسخ: `{ "message": "calendars", "data": CalendarDay[] }`. این مسیر همه روزهای دیتابیس را بدون pagination برمی‌گرداند.

## 6. API رزرو

### ساخت رزرو سانس

`POST /api/reservations` — نیازمند توکن

```json
{
  "calendar_interval_id": 15,
  "user_id": 3,
  "additionals": [
    { "name": "توپ", "price": 50000 }
  ]
}
```

- `calendar_interval_id`: شناسه `VenueTimePrice`
- `user_id`: فعلاً اجباری؛ باید برابر شناسه کاربر واردشده ارسال شود
- `additionals`: اختیاری؛ قیمت هر آیتم در سرور با قیمت سانس جمع می‌شود
- موفق: `201` با خود آبجکت `Reservation`
- سانس قبلاً رزرو شده: `409` با `{ "message": "Slot already reserved" }`
- سانس یا کاربر نامعتبر: `404` یا `422`

جریان پیشنهادی رزرو:

1. دریافت جزئیات مجموعه.
2. دریافت روزهای مجموعه از `venues/calendars/{id}`.
3. انتخاب روز و دریافت سانس‌ها از `venues/time/{id}`.
4. حذف/غیرفعال‌کردن سانس‌هایی که `reservation !== null` دارند.
5. انتخاب امکانات اضافه از `venue.additionals`.
6. نمایش مبلغ نهایی تخمینی و تأیید کاربر.
7. ارسال رزرو؛ در خطای `409` سانس‌ها دوباره دریافت شوند.

### فهرست رزروها

`GET /api/reservations?page=1` — نیازمند توکن، ۱۰ مورد در هر صفحه

- `user`: فقط رزروهای خودش، همراه `venue`
- `venue_admin`: رزروهای مجموعه‌های خودش، همراه `user` و `venue`
- `super_admin`: همه رزروها، همراه `user` و `venue`
- پاسخ مستقیماً `Paginated<Reservation>` است.

### جزئیات رزرو

`GET /api/reservations/{reservationId}` — نیازمند توکن

فقط کاربر صاحب رزرو، مدیر صاحب مجموعه یا سوپرادمین مجاز است. پاسخ شامل `user` و `venue` است؛ عدم دسترسی `403` می‌دهد.

### تغییر وضعیت رزرو

`PATCH /api/reservations/{reservationId}/status` — فقط مدیر مجموعه یا سوپرادمین

```json
{ "status": "confirmed" }
```

مقادیر مجاز: `pending`، `confirmed` و `cancelled`. پاسخ، رزرو به‌روزشده است.

## 7. API پنل مدیر مجموعه

تمام مسیرهای این بخش نیازمند Bearer Token هستند.

### داشبورد مدیر

`GET /api/admin/dashboard`

```ts
interface AdminDashboard {
  stats: {
    total: number;
    today: number;
    pending: number;
    success: number;
    cancelled: number;
    totalRevenue: Money;
    thisMonthRevenue: Money;
  };
  recentReservations: Reservation[];
  pendingReservations: Reservation[];
}
```

### مجموعه‌های قابل مدیریت

- `GET /api/venues/manage/admin?page=1`: صفحه‌بندی مجموعه‌های مدیر؛ سوپرادمین همه را می‌بیند.
- `GET /api/venues/manage/admin/{venueId}`: جزئیات مدیریتی همراه `owner`، `images` و `venue_price`.

### ایجاد مجموعه

`POST /api/admin/venues` — فقط `venue_admin` و `super_admin`

نمونه برای مجموعه ساعتی:

```json
{
  "name": "سالن آزادی",
  "type": "futsal",
  "billing_type": "hourly",
  "address": "تهران، میدان آزادی",
  "capacity": 12,
  "price": 500000,
  "description": "سالن سرپوشیده",
  "is_active": true,
  "additionals": [
    { "name": "توپ", "price": 50000 }
  ],
  "calendars_id": [70, 71],
  "time_schedules": [
    {
      "interval_minutes": 90,
      "ranges": [
        { "from": "08:00", "to": "14:00", "price": 400000 },
        { "from": "14:00", "to": "23:00", "price": 600000 }
      ]
    }
  ]
}
```

قواعد قطعی validation:

- `name`، `type`، `billing_type`، `address` و `is_active` اجباری‌اند.
- `capacity` و `price` عدد صحیح نامنفی هستند.
- `additionals`، `time_schedules` و `calendars_id` در صورت ارسال باید array باشند.
- در کد کنترلر `session` پذیرفته می‌شود، اما دیتابیس فقط `hourly` و `monthly` را قبول می‌کند؛ فعلاً در UI فقط همین دو مقدار نمایش داده شود.
- سانس‌سازی خودکار فقط برای `hourly` انجام می‌شود.
- بازه‌های نامعتبر یا بازه‌ای که از طول interval کوتاه‌تر است، بدون خطای validation نادیده گرفته می‌شود.

پاسخ موفق: `{ "success": true, "data": Venue }`.

### ویرایش مجموعه

`POST /api/venues/admin/manage/update/{venueId}`

بدنه می‌تواند هرکدام از این فیلدها را داشته باشد: `name`، `type`، `billing_type`، `price`، `address`، `capacity`، `description`، `is_active` و `additionals`.

توجه: این مسیر در حال حاضر validation و policy فعال ندارد؛ فرانت باید فقط برای مالک نمایش دهد، اما امنیت باید در بک‌اند اصلاح شود.

### حذف مجموعه

`DELETE /api/venues/{venueId}` — مالک مجموعه یا سوپرادمین

پاسخ: `{ "message": "Venue deleted" }`.

### آپلود تصاویر

`POST /api/venues/upload/{venueId}` — فقط مدیر مجموعه یا سوپرادمین

```ts
const formData = new FormData();
for (const file of files) formData.append("photo[]", file);
await api.post(`/venues/upload/${venueId}`, formData);
```

پاسخ موفق:

```json
{ "success": true, "message": "Files uploaded successfully." }
```

فعلاً محدودیت نوع، حجم و تعداد عکس در کنترلر تعریف نشده است؛ در UI بهتر است فقط تصویر و محدودیت معقول تعداد/حجم پذیرفته شود، ولی این جای validation سرور را نمی‌گیرد.

### تعریف سانس و قیمت برای یک روز

`POST /api/venues/price/{venueId}` — فقط مدیر مجموعه یا سوپرادمین

```json
{
  "calendar_id": 70,
  "interval": 90,
  "price_range": [
    { "from": "08:00", "to": "14:00", "price": 400000 },
    { "from": "14:00", "to": "23:00", "price": 600000 }
  ]
}
```

بازه به قطعات کامل `interval` دقیقه‌ای تقسیم می‌شود. بخش باقی‌مانده کوچک‌تر از interval ساخته نمی‌شود.

## 8. داشبورد کاربر

`GET /api/user/dashboard` — نیازمند توکن

```ts
interface UserDashboard {
  all_reservationCount: number;
  future_reservation: number;
  reservation_price_paid: Money;
  last_reservation: Reservation[];
}
```

نام‌گذاری پاسخ دقیقاً به همین صورت و ترکیبی از snake_case و camelCase است؛ در adapter فرانت آن را یکدست کنید.

## 9. صفحات و مسیرهای پیشنهادی فرانت

### عمومی

| مسیر پیشنهادی | صفحه | داده موردنیاز |
|---|---|---|
| `/` | خانه، آمار و مجموعه‌های منتخب | `venue/dashboard` و `venues` |
| `/venues` | لیست، جست‌وجو و فیلتر مجموعه‌ها | `venues` |
| `/venues/:id` | جزئیات، تصاویر، امکانات و قیمت‌ها | `venues/{id}` |
| `/login` | ورود | `login` سپس `profile` |
| `/register` | ثبت‌نام کاربر عادی | `register` |

### کاربر واردشده

| مسیر پیشنهادی | صفحه |
|---|---|
| `/dashboard` | خلاصه حساب کاربر |
| `/reservations` | رزروهای من |
| `/reservations/:id` | جزئیات رزرو |
| `/venues/:id/reserve` | انتخاب روز، سانس و امکانات اضافه |

### مدیر مجموعه / سوپرادمین

| مسیر پیشنهادی | صفحه |
|---|---|
| `/admin` | داشبورد آماری |
| `/admin/venues` | مجموعه‌های قابل مدیریت |
| `/admin/venues/new` | ساخت مجموعه و برنامه زمانی |
| `/admin/venues/:id` | جزئیات مدیریت مجموعه |
| `/admin/venues/:id/edit` | ویرایش مجموعه |
| `/admin/venues/:id/schedules` | تعریف قیمت و سانس روزها |
| `/admin/reservations` | مدیریت رزروها و وضعیت آن‌ها |

### Route Guard پیشنهادی

- `guestOnly`: ورود و ثبت‌نام
- `authenticated`: داشبورد، رزرو و پروفایل
- `roles: [venue_admin, super_admin]`: تمام مسیرهای `/admin`
- در دریافت `401`، session محلی پاک و کاربر به `/login` هدایت شود.
- در `403` صفحه «عدم دسترسی» نمایش داده شود.

## 10. UX و نمایش داده

- رابط فارسی و `dir="rtl"` باشد.
- تاریخ قابل نمایش از `day_jalali` گرفته شود؛ برای ارسال رزرو، فرانت تاریخ نسازد و شناسه سانس را ارسال کند.
- زمان‌های `HH:mm:ss` برای نمایش به `HH:mm` تبدیل شوند.
- قیمت‌ها با جداکننده هزارگان نمایش داده شوند و واحد پول محصول به‌صورت صریح در UI مشخص شود؛ کد معلوم نمی‌کند مبلغ ریال است یا تومان.
- مجموعه غیرفعال (`is_active = false`) باید badge مشخص داشته باشد؛ بک‌اند فعلاً آن را از فهرست عمومی حذف نمی‌کند.
- وضعیت‌ها: `pending = در انتظار`، `confirmed = تأییدشده`، `cancelled = لغوشده`.
- برای pagination از مقادیر خود پاسخ استفاده کنید و روی تعداد کلاینتی ثابت تکیه نکنید.
- برای `422` خطاهای Laravel را از `errors[field][]` زیر همان فیلد نمایش دهید.

نمونه مدیریت خطا:

```ts
type LaravelValidationError = {
  message: string;
  errors: Record<string, string[]>;
};

function getApiMessage(error: any): string {
  if (error.response?.status === 409) return "این سانس قبلاً رزرو شده است.";
  if (error.response?.status === 401) return "لطفاً دوباره وارد شوید.";
  if (error.response?.status === 403) return "اجازه انجام این عملیات را ندارید.";
  return error.response?.data?.message ?? "خطایی رخ داده است.";
}
```

## 11. مشکلات و ابهام‌های فعلی بک‌اند

این موارد از روی کد فعلی استخراج شده‌اند و باید قبل از اتکای کامل فرانت تعیین تکلیف شوند:

1. **وضعیت درآمد اشتباه است:** دیتابیس و API وضعیت `confirmed` دارند، ولی داشبورد مدیر درآمد و تعداد را با وضعیت `success` محاسبه می‌کند؛ بنابراین مقادیر success و درآمد فعلاً صفر خواهند بود.
2. **رزرو به `user_id` ورودی اعتماد می‌کند:** کاربر واردشده می‌تواند شناسه کاربر دیگری را ارسال کند. سرور باید از `auth()->id()` استفاده کند و فیلد `user_id` را از بدنه حذف کند.
3. **بررسی رزرو تکراری ناقص است:** رکوردهای `cancelled` نیز سانس را برای همیشه اشغال می‌کنند و constraint یکتا در دیتابیس وجود ندارد؛ درخواست همزمان هم ممکن است رزرو تکراری بسازد.
4. **داشبورد کاربر داده اشتباه دارد:** `future_reservation` در عمل تاریخ‌های قبل از امروز را می‌شمارد. همچنین `last_reservation` اصلاً با کاربر جاری فیلتر نشده و ممکن است رزروهای همه کاربران را افشا کند.
5. **ثبت‌نام phone با schema سازگار نیست:** کنترلر `phone` را ذخیره می‌کند ولی migration جدول users ستون `phone` ندارد.
6. **نوع billing ناسازگار است:** validation ساخت مجموعه مقدار `session` را می‌پذیرد ولی enum دیتابیس آن را رد می‌کند.
7. **ویرایش مجموعه محافظت کافی ندارد:** route ویرایش role middleware ندارد، policy داخل کنترلر کامنت شده و validation هم انجام نمی‌شود.
8. **دسترسی جزئیات مدیریت برای سوپرادمین ناقص است:** `getAdminVenue` فقط `owner_id === current user` را می‌پذیرد، در نتیجه سوپرادمین مجموعه دیگران را در این مسیر نمی‌بیند.
9. **آپلود تصویر مالکیت را بررسی نمی‌کند:** هر venue_admin می‌تواند با شناسه مجموعه دیگر روی آن عکس آپلود کند. تعریف سانس/قیمت نیز همین ریسک را دارد.
10. **خروج Sanctum مبهم است:** `auth()->logout()` معمولاً برای توکن Sanctum روش حذف توکن جاری نیست؛ بهتر است `currentAccessToken()->delete()` استفاده شود.
11. **مدل رابطه رزرو ناسازگار است:** رابطه `Reservation::interval()` به مدل تعریف‌نشده `CalendarInterval` اشاره می‌کند، در حالی که کنترلر از `VenueTimePrice` استفاده می‌کند.
12. **اعتبارسنجی سانس و قیمت کافی نیست:** در endpoint قیمت‌گذاری، validation ورودی وجود ندارد و type hint `calendarId` با مقدار پیش‌فرض null ناسازگار است.
13. **ساخت مجموعه می‌تواند calendar_id تهی بسازد:** migration آن را nullable نکرده، ولی کنترلر در نبود `calendars_id` مقدار `null` در نظر می‌گیرد.
14. **فرمت پاسخ لیست مجموعه‌ها یکسان نیست:** wrapper پاسخ عمومی با پاسخ مدیر و سوپرادمین فرق دارد؛ adapter فرانت لازم است یا API باید استاندارد شود.
15. **واحد پول تعریف نشده است:** نام فیلد فقط `price` است و مشخص نیست اعداد ریال‌اند یا تومان.
16. **مسیر عمومی تکراری وجود دارد:** `/venues/user` همان متد `/venues` را اجرا می‌کند و نام آن با رفتار واقعی همخوان نیست.
17. **نبود CORS سفارشی قابل مشاهده:** اگر فرانت روی origin جدا اجرا شود، تنظیم CORS باید در محیط نهایی بررسی شود.

## 12. ترتیب پیشنهادی پیاده‌سازی

1. لایه API، interceptor توکن، مدیریت `401/403/422` و typeها.
2. Auth store و بازیابی session با `/profile`.
3. صفحات عمومی لیست و جزئیات مجموعه.
4. جریان انتخاب تقویم، سانس و ساخت رزرو با مدیریت `409`.
5. رزروها و داشبورد کاربر.
6. route guard نقش‌ها و داشبورد مدیر.
7. CRUD مجموعه، آپلود تصاویر و قیمت‌گذاری سانس‌ها.
8. اصلاح موارد بحرانی بک‌اند، سپس تست end-to-end جریان‌های اصلی.

## 13. چک‌لیست پذیرش فرانت

- کاربر بعد از refresh با توکن معتبر وارد باقی می‌ماند.
- منو و routeها متناسب با نقش نمایش داده می‌شوند.
- لیست‌ها loading، empty، error و pagination دارند.
- صفحه مجموعه تصاویر خراب را با placeholder جایگزین می‌کند.
- سانس رزروشده قابل انتخاب نیست و خطای `409` باعث refresh سانس‌ها می‌شود.
- مبلغ نهایی قبل از تأیید رزرو نمایش داده می‌شود، اما پاسخ سرور منبع نهایی مبلغ است.
- خطاهای validation کنار فیلد مربوط نمایش داده می‌شوند.
- عملیات حذف و تغییر وضعیت confirmation دارد.
- تاریخ شمسی و زمان‌ها در تمام صفحات یکدست‌اند.
- UI در موبایل و دسکتاپ و در حالت RTL درست کار می‌کند.
- هیچ صفحه مدیریتی تنها به مخفی‌کردن دکمه برای امنیت متکی نیست؛ مجوز نهایی باید در بک‌اند اعمال شود.

