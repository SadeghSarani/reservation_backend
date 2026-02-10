<?php

namespace App\Console\Commands;

use App\Models\Calendar as ModelsCalendar;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

class calendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calendar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $lastCalendar = ModelsCalendar::orderBy('id', 'desc')->first();

        if ($lastCalendar === null) {
            $startDay = Carbon::now();
        } else {
            $startDay = Jalalian::fromFormat(
                'Y/m/d',
                $lastCalendar->day_jalali
            )->toCarbon()->addDay();
        }

        for ($i = 0; $i <= 300; $i++) {

            sleep(1);
            $carbonDate = $startDay->copy()->addDays($i);
            $jalali = Jalalian::fromCarbon($carbonDate);

            $year  = $jalali->getYear();
            $month = $jalali->getMonth();
            $day   = $jalali->getDay();

            $response = Http::timeout(10)
                ->get("https://holidayapi.ir/jalali/{$year}/{$month}/{$day}");

            if (! $response->ok()) {
                continue; // or log error
            }

            $data = $response->json();

            // Friday check (Jalali: 6 = Friday)
            $isFriday = $jalali->getDayOfWeek() === 6;

            $isHoliday = $isFriday || ($data['is_holiday'] ?? false);

            // collect holiday events
            $events = collect($data['events'] ?? [])
                ->where('is_holiday', true)
                ->pluck('description')
                ->unique()
                ->implode(' | ');

            $calendar = [
                'day'         => $carbonDate,
                'day_jalali'  => $jalali->format('Y/m/d'),
                'holiday'     => $isHoliday,
                'event'       => $isHoliday
                    ? ($isFriday ? 'Friday' : $events)
                    : null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            ModelsCalendar::create($calendar);


            echo $calendar['day_jalali'] . PHP_EOL;
        }
    }
}
