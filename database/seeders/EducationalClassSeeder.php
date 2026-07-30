<?php

namespace Database\Seeders;

use App\Models\EducationalClass;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class EducationalClassSeeder extends Seeder
{
    public function run(): void
    {
        $tennisCoach = User::where('email', 'coach@reservation.test')->firstOrFail();
        $fitnessCoach = User::where('email', 'fitness@reservation.test')->firstOrFail();
        $venueOwner = User::where('email', 'venue@reservation.test')->firstOrFail();
        $tennisVenue = Venue::where('name', 'زمین تنیس انقلاب')->firstOrFail();
        $futsalVenue = Venue::where('name', 'سالن فوتسال آزادی')->firstOrFail();

        $classes = [
            [
                'slug' => 'beginner-tennis-seed', 'instructor_id' => $tennisCoach->id, 'venue_id' => $tennisVenue->id,
                'title' => 'دوره مقدماتی تنیس', 'description' => 'آموزش اصول تنیس، سرویس و ضربات پایه برای افراد مبتدی.',
                'category' => 'tennis', 'level' => 'beginner', 'capacity' => 12, 'price' => 4500000,
                'location' => null, 'schedule' => [['day' => 'شنبه', 'start_time' => '10:00', 'end_time' => '11:30'], ['day' => 'دوشنبه', 'start_time' => '10:00', 'end_time' => '11:30']],
                'features' => ['۸ جلسه آموزشی', 'تجهیزات تمرینی', 'گواهی پایان دوره'],
            ],
            [
                'slug' => 'advanced-tennis-seed', 'instructor_id' => $tennisCoach->id, 'venue_id' => $tennisVenue->id,
                'title' => 'تنیس پیشرفته و مسابقه', 'description' => 'تکنیک‌های پیشرفته، تاکتیک مسابقه و تمرین آمادگی رقابتی.',
                'category' => 'tennis', 'level' => 'advanced', 'capacity' => 8, 'price' => 6800000,
                'location' => null, 'schedule' => [['day' => 'یکشنبه', 'start_time' => '17:00', 'end_time' => '19:00']],
                'features' => ['۶ جلسه تخصصی', 'آنالیز عملکرد'],
            ],
            [
                'slug' => 'functional-fitness-seed', 'instructor_id' => $fitnessCoach->id, 'venue_id' => null,
                'title' => 'بدنسازی فانکشنال', 'description' => 'تمرینات گروهی قدرت، استقامت و انعطاف‌پذیری.',
                'category' => 'fitness', 'level' => 'all', 'capacity' => 18, 'price' => 3200000,
                'location' => 'پارک آب و آتش، فضای تمرین گروهی',
                'schedule' => [['day' => 'شنبه', 'start_time' => '07:00', 'end_time' => '08:00'], ['day' => 'سه‌شنبه', 'start_time' => '07:00', 'end_time' => '08:00']],
                'features' => ['۱۰ جلسه', 'برنامه تمرینی شخصی'],
            ],
            [
                'slug' => 'futsal-kids-seed', 'instructor_id' => $venueOwner->id, 'venue_id' => $futsalVenue->id,
                'title' => 'مدرسه فوتسال کودکان', 'description' => 'آموزش مهارت‌های پایه فوتسال برای کودکان ۸ تا ۱۲ سال.',
                'category' => 'futsal', 'level' => 'beginner', 'capacity' => 20, 'price' => 2800000,
                'location' => null, 'schedule' => [['day' => 'پنجشنبه', 'start_time' => '15:00', 'end_time' => '16:30']],
                'features' => ['۸ جلسه', 'لباس تمرین', 'گزارش پیشرفت'],
            ],
        ];

        foreach ($classes as $data) {
            EducationalClass::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    ...$data,
                    'registration_deadline' => now()->addDays(14)->endOfDay(),
                    'starts_on' => now()->addDays(16)->toDateString(),
                    'ends_on' => now()->addDays(60)->toDateString(),
                    'status' => 'published',
                ],
            );
        }
    }
}
