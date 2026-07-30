<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueTimePrice;
use Illuminate\Database\Seeder;
use Morilog\Jalali\Jalalian;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'venue@reservation.test')->firstOrFail();
        $venues = [
            [
                'name' => 'سالن فوتسال آزادی', 'type' => 'futsal', 'billing_type' => 'hourly',
                'address' => 'تهران، مجموعه ورزشی آزادی', 'capacity' => 20, 'price' => 1200000,
                'description' => 'سالن استاندارد فوتسال با رختکن و پارکینگ.',
                'additionals' => [['name' => 'توپ', 'price' => 100000], ['name' => 'داور', 'price' => 350000]],
            ],
            [
                'name' => 'سالن فوتسال المپیک', 'type' => 'futsal', 'billing_type' => 'hourly',
                'address' => 'تهران، شهرک غرب، بلوار فرحزادی', 'capacity' => 18, 'price' => 1350000,
                'description' => 'سالن فوتسال سرپوشیده با کفپوش حرفه‌ای و تهویه مناسب.',
                'additionals' => [['name' => 'توپ', 'price' => 100000], ['name' => 'فیلم‌برداری بازی', 'price' => 450000]],
            ],
            [
                'name' => 'زمین تنیس انقلاب', 'type' => 'tennis', 'billing_type' => 'hourly',
                'address' => 'تهران، مجموعه ورزشی انقلاب', 'capacity' => 4, 'price' => 900000,
                'description' => 'زمین تنیس روباز مناسب تمرین و مسابقه.',
                'additionals' => [['name' => 'راکت', 'price' => 150000], ['name' => 'بسته توپ', 'price' => 120000]],
            ],
            [
                'name' => 'زمین تنیس آفتاب', 'type' => 'tennis', 'billing_type' => 'hourly',
                'address' => 'تهران، سعادت‌آباد، بلوار دریا', 'capacity' => 4, 'price' => 1100000,
                'description' => 'زمین تنیس استاندارد با نور شب و فضای استراحت.',
                'additionals' => [['name' => 'راکت حرفه‌ای', 'price' => 220000], ['name' => 'توپ تمرینی', 'price' => 130000]],
            ],
            [
                'name' => 'سالن والیبال پاس', 'type' => 'volleyball', 'billing_type' => 'hourly',
                'address' => 'تهران، میدان هروی', 'capacity' => 24, 'price' => 1500000,
                'description' => 'سالن سرپوشیده والیبال با جایگاه تماشاچی.',
                'additionals' => [['name' => 'توپ والیبال', 'price' => 100000]],
            ],
            [
                'name' => 'سالن والیبال ستارگان', 'type' => 'volleyball', 'billing_type' => 'hourly',
                'address' => 'تهران، نارمک، میدان هفت‌حوض', 'capacity' => 30, 'price' => 1650000,
                'description' => 'سالن والیبال مجهز با رختکن، دوش و جایگاه تماشاچی.',
                'additionals' => [['name' => 'توپ والیبال', 'price' => 120000], ['name' => 'داور', 'price' => 400000]],
            ],
            [
                'name' => 'باشگاه بدنسازی انرژی', 'type' => 'gym', 'billing_type' => 'hourly',
                'address' => 'تهران، یوسف‌آباد، خیابان فتحی شقاقی', 'capacity' => 35, 'price' => 450000,
                'description' => 'باشگاه بدنسازی مجهز به دستگاه‌های هوازی و قدرتی.',
                'additionals' => [['name' => 'مربی خصوصی', 'price' => 500000], ['name' => 'حوله', 'price' => 50000]],
            ],
            [
                'name' => 'باشگاه بدنسازی تندرست', 'type' => 'gym', 'billing_type' => 'hourly',
                'address' => 'تهران، پاسداران، خیابان گلستان', 'capacity' => 45, 'price' => 550000,
                'description' => 'مجموعه بدنسازی و آمادگی جسمانی با فضای تمرین گروهی.',
                'additionals' => [['name' => 'برنامه تمرینی', 'price' => 300000], ['name' => 'کمد اختصاصی', 'price' => 80000]],
            ],
            [
                'name' => 'زمین فوتبال چمن سبز', 'type' => 'football', 'billing_type' => 'hourly',
                'address' => 'تهران، حکیمیه، مجموعه ورزشی ساحل', 'capacity' => 30, 'price' => 2400000,
                'description' => 'زمین فوتبال چمن مصنوعی مناسب بازی یازده نفره.',
                'additionals' => [['name' => 'توپ فوتبال', 'price' => 120000], ['name' => 'داور', 'price' => 500000]],
            ],
            [
                'name' => 'زمین فوتبال آزادی غرب', 'type' => 'football', 'billing_type' => 'hourly',
                'address' => 'تهران، اکباتان، فاز دو', 'capacity' => 26, 'price' => 2200000,
                'description' => 'زمین فوتبال با نورپردازی شب، رختکن و پارکینگ.',
                'additionals' => [['name' => 'توپ فوتبال', 'price' => 100000], ['name' => 'جلیقه تیمی', 'price' => 180000]],
            ],
            [
                'name' => 'سالن بسکتبال آرارات', 'type' => 'basketball', 'billing_type' => 'hourly',
                'address' => 'تهران، ونک، خیابان آرارات', 'capacity' => 20, 'price' => 1750000,
                'description' => 'سالن بسکتبال استاندارد با کفپوش پارکت و اسکوربرد.',
                'additionals' => [['name' => 'توپ بسکتبال', 'price' => 100000], ['name' => 'داور', 'price' => 400000]],
            ],
            [
                'name' => 'سالن بسکتبال هدف', 'type' => 'basketball', 'billing_type' => 'hourly',
                'address' => 'تهران، پیروزی، خیابان پنجم نیروهوایی', 'capacity' => 24, 'price' => 1550000,
                'description' => 'سالن سرپوشیده بسکتبال مناسب تمرین و مسابقه دوستانه.',
                'additionals' => [['name' => 'توپ بسکتبال', 'price' => 90000], ['name' => 'تابلوی امتیاز', 'price' => 150000]],
            ],
        ];

        foreach ($venues as $venueData) {
            $venue = Venue::updateOrCreate(
                ['owner_id' => $owner->id, 'name' => $venueData['name']],
                [...$venueData, 'is_active' => true],
            );
            $this->seedTimes($venue);
        }
    }

    private function seedTimes(Venue $venue): void
    {
        $slots = [
            ['08:00', '09:30', 1], ['09:30', '11:00', 1], ['16:00', '17:30', 1.2],
            ['17:30', '19:00', 1.2], ['19:00', '20:30', 1.4], ['20:30', '22:00', 1.4],
        ];

        foreach (range(1, 7) as $offset) {
            $date = now()->addDays($offset)->startOfDay();
            $calendar = Calendar::updateOrCreate(
                ['day' => $date],
                ['day_jalali' => Jalalian::fromCarbon($date)->format('Y/m/d'), 'holiday' => false, 'event' => null],
            );

            foreach ($slots as [$start, $end, $multiplier]) {
                VenueTimePrice::updateOrCreate(
                    ['venue_id' => $venue->id, 'calendar_id' => $calendar->id, 'start_time' => $start, 'end_time' => $end],
                    ['price' => (string) round((float) $venue->price * $multiplier)],
                );
            }
        }
    }
}
