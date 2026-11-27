<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Models\Announcer;
use App\Models\Program;
use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * OZ Bandung's real programs, weekly schedule and announcers, with their
 * photos from database/seeders/images (WebP copies of brand/programs and
 * brand/announcer).
 *
 * Safe to re-run: records are matched by slug, and existing ones are left
 * exactly as they are, so edits made in the admin survive.
 *
 *     php artisan db:seed --class=OzBandungRosterSeeder
 */
class OzBandungRosterSeeder extends Seeder
{
    /**
     * Title => weekly slots in WIB. 00:00 as an end time means midnight.
     */
    private const PROGRAMS = [
        'OZ Morning Rush' => [['weekdays', '07:00', '09:00']],
        'RILIS' => [['weekdays', '09:00', '12:00']],
        'OZ Rewind' => [[[DayOfWeek::Tuesday, DayOfWeek::Wednesday, DayOfWeek::Thursday], '12:00', '14:00']],
        'OZ We Go' => [['weekdays', '15:00', '18:00']],
        'Main Aman' => [[[DayOfWeek::Monday], '18:00', '21:00']],
        'Kamus Ozzer' => [[[DayOfWeek::Tuesday, DayOfWeek::Friday], '18:00', '21:00']],
        'Ozriental' => [[[DayOfWeek::Wednesday], '18:00', '21:00']],
        'OZ Dreamland' => [[[DayOfWeek::Monday, DayOfWeek::Friday], '21:00', '00:00']],
        'Sidelabz' => [[[DayOfWeek::Tuesday], '21:00', '23:00']],
        'Carita Si Miming & Bebeg' => [[[DayOfWeek::Wednesday], '21:00', '23:00']],
        'OZ Cool School' => [[[DayOfWeek::Saturday, DayOfWeek::Sunday], '07:00', '09:00']],
        'OZCLUSIVE' => [],
        'Departemen Lain-Lain' => [],
        'Siniar Diluar' => [],
        'Love Cinta' => [],
        'Percikan Iman' => [],
    ];

    /**
     * [stage name, real name, program titles]. No real name: the stage name is used for both.
     */
    private const ANNOUNCERS = [
        ['Abel', 'Abel Hamdani', ['OZ We Go']],
        ['Kayra Dama', 'Alifah Dama Kahathur', ['OZ Morning Rush']],
        ['Alma', 'Alma Zoraya', ['RILIS', 'OZ Dreamland', 'Kamus Ozzer']],
        ['Anton', 'Antoni Lovendio', ['Siniar Diluar', 'Main Aman']],
        ['AJ', 'Aizira Fariza', ['RILIS', 'Kamus Ozzer']],
        ['Firman', 'Firman Oktaviawan', []],
        ['Maury Ghita', 'Ghita Mauritsa', ['OZ Rewind']],
        ['Haikal', 'Haikal Aksara', ['Sidelabz']],
        ['Hasbi', 'Hasbi Erlangga', []],
        ['Amoy', 'Mochamad Fahmi', ['Siniar Diluar', 'Main Aman']],
        ['Momon', 'Monica Melvriana Putri', ['Ozriental']],
        ['Nabila Oriza', 'Nabila Zahra', ['OZ Rewind']],
        ['Aretha', 'Nur Aretha Zabarina', ['RILIS', 'OZ Dreamland']],
        ['Ramus', 'Rafi Musthafa', ['OZ We Go']],
        ['VJ', 'Vickry Jayahadi', ['Ozriental', 'OZ Morning Rush']],
        ['Jazz', 'Jazz Permana', []],
        ['Kiming', null, ['Carita Si Miming & Bebeg']],
        ['Gebeg', null, ['Carita Si Miming & Bebeg']],
    ];

    public function run(): void
    {
        $station = Station::query()->where('slug', 'oz-bandung')->firstOrFail();

        $programs = collect(self::PROGRAMS)->map(fn (array $slots, string $title): Program => $this->seedProgram($station, $title, $slots));

        foreach (self::ANNOUNCERS as [$stageName, $name, $titles]) {
            $programIds = array_values($programs->only($titles)->map(fn (Program $program): int => $program->id)->all());
            $this->seedAnnouncer($station, $stageName, $name ?? $stageName, $programIds);
        }
    }

    /**
     * @param  list<array{0: string|list<DayOfWeek>, 1: string, 2: string}>  $slots
     */
    private function seedProgram(Station $station, string $title, array $slots): Program
    {
        /** @var Program $program */
        $program = $station->programs()->firstOrCreate(
            ['slug' => Str::slug($title)],
            ['title' => $title, 'is_active' => true],
        );

        if ($program->wasRecentlyCreated) {
            foreach ($slots as [$days, $start, $end]) {
                $resolvedDays = $days === 'weekdays' ? DayOfWeek::weekdays() : (is_array($days) ? $days : []);
                foreach ($resolvedDays as $day) {
                    $program->schedules()->create(['day_of_week' => $day, 'start_time' => $start, 'end_time' => $end]);
                }
            }

            $this->attachPhoto($program, 'cover', "programs/{$program->slug}.webp");
        }

        return $program;
    }

    /**
     * @param  list<int>  $programIds
     */
    private function seedAnnouncer(Station $station, string $stageName, string $name, array $programIds): void
    {
        /** @var Announcer $announcer */
        $announcer = $station->announcers()->firstOrCreate(
            ['slug' => Str::slug($stageName)],
            ['name' => $name, 'stage_name' => $stageName, 'is_active' => true],
        );

        if ($announcer->wasRecentlyCreated) {
            $announcer->programs()->attach($programIds);
            $this->attachPhoto($announcer, 'avatar', 'announcers/'.Str::slug($name).'.webp');
        }
    }

    private function attachPhoto(Program|Announcer $model, string $collection, string $file): void
    {
        // Tests skip photos: they would be written to the real public disk and converted on every seed.
        if (app()->runningUnitTests()) {
            return;
        }

        $model->addMedia(database_path("seeders/images/{$file}"))
            ->preservingOriginal()
            ->toMediaCollection($collection);
    }
}
