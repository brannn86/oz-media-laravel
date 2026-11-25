<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Enums\SocialPlatform;
use App\Models\Announcer;
use App\Models\Program;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sample content for the flagship station. Safe to re-run: content is only
 * seeded when the station has no programs yet.
 */
class OzBandungSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $station = $this->seedStation();

            if ($station->programs()->exists()) {
                $this->command?->info('OZ Bandung already has content, skipping sample data.');

                return;
            }

            $programs = $this->seedPrograms($station);
            $this->seedSchedules($programs);
            $this->seedAnnouncers($station, $programs);
            $this->seedPosts($station);
            $this->seedEvents($station);
            $this->seedSocialHighlights($station);
        });
    }

    private function seedStation(): Station
    {
        return Station::query()->updateOrCreate(['slug' => 'oz-bandung'], [
            'name' => 'OZ Bandung',
            'tagline' => 'Bandung\'s No.1 Hit Music Station',
            'stream_audio_url' => 'https://streaming.ozradio.id:8443/ozbandung',
            'stream_title_format' => 'title_artist',
            'stream_video_url' => 'https://www.youtube.com/embed/live_stream?channel=OZ_BANDUNG_CHANNEL_ID',
            'contact_info' => [
                'email' => 'hello@ozmedianet.com',
                'phone' => '+62 22 0000 0000',
                'address' => 'Jl. Contoh No. 1, Bandung, Jawa Barat 40115',
                'whatsapp' => '+6281200000000',
            ],
            'social_links' => [
                'instagram' => 'https://www.instagram.com/ozradiobandung',
                'tiktok' => 'https://www.tiktok.com/@ozradiobandung',
                'youtube' => 'https://www.youtube.com/@ozradiobandung',
                'threads' => 'https://www.threads.com/@ozradiobandung',
            ],
            'about_content' => <<<'HTML'
                <p>OZ Bandung is the flagship station of OZ Media, broadcasting the biggest hits, the freshest local music and the stories that matter to Bandung's young urban listeners.</p>
                <p>From the Breakfast Club to the Night Session, our announcers keep the city company on air, online and on every social platform.</p>
                HTML,
            'privacy_policy_content' => <<<'HTML'
                <h2>Privacy Policy</h2>
                <p>This sample policy describes how OZ Media collects and uses information when you listen, watch or interact with OZ Bandung online. Replace it with the policy approved by legal before launch.</p>
                <h3>Information we collect</h3>
                <p>We collect information you give us directly (for example when you contact us or join a contest) and basic analytics about how our website and player are used.</p>
                <h3>How we use it</h3>
                <p>We use this information to run our services, improve our programming and, with your consent, to send you news about OZ events.</p>
                <h3>Contact</h3>
                <p>Questions? Email hello@ozmedianet.com.</p>
                HTML."\n".file_get_contents(database_path('content/privacy-measurement.html')),
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, Program>
     */
    private function seedPrograms(Station $station): array
    {
        $programs = [
            'early-bird' => ['Early Bird', 'Gentle hits and the first traffic and weather updates of the day for Bandung\'s early risers.'],
            'breakfast-club' => ['OZ Breakfast Club', 'The city\'s favourite morning show: big hits, trending topics, games and calls from listeners on their way to school and work.'],
            'midday-mix' => ['Midday Mix', 'Non-stop hit music to power you through the working day, plus lunchtime requests.'],
            'afternoon-hangout' => ['Afternoon Hangout', 'Light chat, campus news and the newest releases for the after-school crowd.'],
            'drive-time' => ['OZ Drive Time', 'Traffic updates across Pasteur, Dago and Soekarno-Hatta with the hits that get you home.'],
            'night-session' => ['OZ Night Session', 'Deep cuts, new local artists and in-studio guest sessions.'],
            'late-night-chill' => ['Late Night Chill', 'Lo-fi, R&B and acoustic tracks to wind down the day.'],
            'weekend-breakfast' => ['Weekend Breakfast', 'A relaxed weekend start with brunch spots, events around town and feel-good music.'],
            'top-40' => ['OZ Top 40 Countdown', 'The 40 most-requested songs of the week, counted down every Saturday and replayed on Sunday.'],
            'weekend-mix' => ['Weekend Mix', 'Party starters and throwbacks to soundtrack your weekend plans.'],
            'saturday-night-party' => ['Saturday Night Party', 'Guest DJ mixes broadcast live from the OZ studio.'],
            'sunday-acoustic' => ['Sunday Acoustic', 'Stripped-back versions of your favourite songs and live acoustic performances.'],
            'throwback-sunday' => ['Throwback Sunday', 'Hits from the 90s and 2000s that Bandung grew up with.'],
        ];

        return collect($programs)->map(fn (array $program, string $slug): Program => $station->programs()->create([
            'slug' => $slug,
            'title' => $program[0],
            'description' => $program[1],
            'is_active' => true,
        ]))->all();
    }

    /**
     * Weekly grid in WIB (Asia/Jakarta). 00:00 as an end time means midnight.
     *
     * @param  array<string, Program>  $programs
     */
    private function seedSchedules(array $programs): void
    {
        $weekday = [
            ['early-bird', '05:00', '06:00'],
            ['breakfast-club', '06:00', '10:00'],
            ['midday-mix', '10:00', '13:00'],
            ['afternoon-hangout', '13:00', '16:00'],
            ['drive-time', '16:00', '19:00'],
            ['night-session', '19:00', '22:00'],
            ['late-night-chill', '22:00', '00:00'],
        ];

        $grid = [
            ...array_map(fn (DayOfWeek $day): array => [$day, $weekday], DayOfWeek::weekdays()),
            [DayOfWeek::Saturday, [
                ['weekend-breakfast', '07:00', '10:00'],
                ['top-40', '10:00', '13:00'],
                ['weekend-mix', '13:00', '18:00'],
                ['saturday-night-party', '20:00', '00:00'],
            ]],
            [DayOfWeek::Sunday, [
                ['sunday-acoustic', '08:00', '11:00'],
                ['top-40', '11:00', '14:00'],
                ['throwback-sunday', '16:00', '19:00'],
                ['late-night-chill', '21:00', '00:00'],
            ]],
        ];

        foreach ($grid as [$day, $slots]) {
            foreach ($slots as [$slug, $start, $end]) {
                $programs[$slug]->schedules()->create([
                    'day_of_week' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                ]);
            }
        }
    }

    /**
     * @param  array<string, Program>  $programs
     */
    private function seedAnnouncers(Station $station, array $programs): void
    {
        $announcers = [
            ['Raka Pratama', 'Raka', 'Morning person by force, coffee addict by choice. Has hosted the Breakfast Club since day one.', ['breakfast-club', 'weekend-breakfast']],
            ['Nadia Putri Lestari', 'Nadia', 'Bandung-born music nerd who loves discovering local indie bands before everyone else.', ['breakfast-club', 'night-session']],
            ['Dimas Ardiansyah', 'Dimas', 'Your traffic-dodging guide home every weekday afternoon.', ['drive-time', 'top-40']],
            ['Salsabila Rahma', 'Sasa', 'Campus reporter turned announcer, keeping you company through the afternoon.', ['afternoon-hangout', 'midday-mix']],
            ['Gilang Ramadhan', 'DJ Gilang', 'Resident DJ behind the Saturday Night Party mixes.', ['saturday-night-party', 'weekend-mix']],
            ['Ayu Kartika', 'Ayu', 'Soft voice for late nights and Sunday mornings.', ['late-night-chill', 'sunday-acoustic', 'throwback-sunday']],
        ];

        foreach ($announcers as [$name, $stageName, $bio, $programSlugs]) {
            $handle = str($stageName)->lower()->replace(' ', '');

            /** @var Announcer $announcer */
            $announcer = $station->announcers()->create([
                'name' => $name,
                'stage_name' => $stageName,
                'bio' => $bio,
                'social_links' => [
                    'instagram' => "https://www.instagram.com/{$handle}.oz",
                    'tiktok' => "https://www.tiktok.com/@{$handle}.oz",
                    'x' => null,
                ],
                'is_active' => true,
            ]);

            $announcer->programs()->attach(collect($programSlugs)->map(fn (string $slug): int => $programs[$slug]->id));
        }
    }

    private function seedPosts(Station $station): void
    {
        $author = User::query()->where('email', config('oz.admin.email'))->first();

        $posts = [
            ['OZ Bandung Luncurkan Platform Digital Baru', 'Website baru OZ Bandung kini hadir dengan live streaming audio dan video, jadwal siaran, dan berita terkini.', 2, PostCategory::Media],
            ['5 Band Indie Bandung yang Wajib Kamu Dengar Bulan Ini', 'Dari shoegaze sampai pop-punk, ini rekomendasi musisi lokal pilihan tim Night Session.', 5, PostCategory::ArtsAndCulture],
            ['Tangga Lagu OZ Top 40: Pendatang Baru Langsung Masuk 10 Besar', 'Minggu ini tiga lagu baru langsung menembus sepuluh besar OZ Top 40 Countdown.', 7, PostCategory::Entertainment],
            ['Tips Menghindari Macet di Jalur Pasteur Saat Libur Panjang', 'Tim Drive Time merangkum jalur alternatif dan jam terbaik untuk berangkat.', 12, PostCategory::Environment],
            ['Di Balik Layar: Sehari Bersama Tim OZ Breakfast Club', 'Mulai jam 4 pagi, beginilah persiapan siaran pagi favorit Bandung.', 18, PostCategory::Media],
            ['OZ Bandung Gelar Konser Amal untuk Pendidikan Anak', 'Seluruh hasil penjualan tiket akan disalurkan untuk beasiswa pelajar di Jawa Barat.', 25, PostCategory::Education],
        ];

        foreach ($posts as [$title, $excerpt, $daysAgo, $category]) {
            $station->posts()->create([
                'author_id' => $author?->id,
                'title' => $title,
                'category' => $category,
                'excerpt' => $excerpt,
                'body' => $this->articleBody($excerpt),
                'status' => PostStatus::Published,
                'published_at' => now()->subDays($daysAgo)->setTime(9, 0),
                'meta_description' => $excerpt,
            ]);
        }

        $station->posts()->create([
            'author_id' => $author?->id,
            'title' => 'Jadwal Siaran Spesial Akhir Tahun',
            'category' => PostCategory::Entertainment,
            'excerpt' => 'Program spesial OZ Bandung untuk menemani malam pergantian tahun.',
            'body' => $this->articleBody('Program spesial OZ Bandung untuk menemani malam pergantian tahun.'),
            'status' => PostStatus::Published,
            'published_at' => now()->addDays(5)->setTime(8, 0),
        ]);

        $station->posts()->create([
            'author_id' => $author?->id,
            'title' => 'Draft: Wawancara Eksklusif dengan Musisi Tamu Night Session',
            'excerpt' => 'Masih dalam proses penyuntingan.',
            'body' => '<p>Transkrip wawancara akan ditambahkan di sini.</p>',
            'status' => PostStatus::Draft,
        ]);
    }

    private function articleBody(string $lead): string
    {
        return <<<HTML
            <p><strong>{$lead}</strong></p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus posuere velit aliquet. Cras mattis consectetur purus sit amet fermentum.</p>
            <h2>Selengkapnya</h2>
            <p>Donec ullamcorper nulla non metus auctor fringilla. Maecenas faucibus mollis interdum. Dengarkan pembahasan lengkapnya di OZ Bandung dan ikuti media sosial kami untuk update terbaru.</p>
            HTML;
    }

    private function seedEvents(Station $station): void
    {
        $events = [
            ['OZ Charity Night 2026', 'OZ Bandung', true, 'Sabuga, Jl. Tamansari No. 73, Bandung', now()->addWeeks(3)->setTime(19, 0), 5, 'Konser amal tahunan OZ Bandung bersama musisi tamu.', 'https://tickets.example.com/oz-charity-night'],
            ['OZ Campus Tour: ITB', 'OZ Bandung', true, 'Institut Teknologi Bandung', now()->addWeeks(5)->setTime(15, 0), 3, 'Siaran langsung, games dan penampilan band kampus.', null],
            ['Bandung Indie Fest', 'Bandung Creative Collective', false, 'Lapangan Gasibu, Bandung', now()->addMonths(2)->startOfWeek()->addDays(5)->setTime(16, 0), 6, 'Festival musik indie dengan lebih dari 20 band lokal. OZ Bandung adalah media partner resmi.', 'https://tickets.example.com/bandung-indie-fest'],
            ['OZ Breakfast Club Meet & Greet', 'OZ Bandung', true, 'Paris Van Java Mall, Bandung', now()->subWeeks(2)->setTime(10, 0), 3, 'Ketemu langsung dengan Raka dan Nadia dari OZ Breakfast Club.', null],
            ['Dago Car Free Day Session', 'Komunitas Dago Bergerak', false, 'Jl. Ir. H. Juanda (Dago), Bandung', now()->subWeeks(5)->setTime(6, 0), 4, 'OZ Bandung meramaikan Car Free Day dengan live music akustik.', null],
        ];

        foreach ($events as [$title, $organizer, $isOz, $location, $start, $hours, $description, $ticketUrl]) {
            /** @var Carbon $start */
            $station->events()->create([
                'title' => $title,
                'organizer_name' => $organizer,
                'is_oz_event' => $isOz,
                'location' => $location,
                'start_date' => $start,
                'end_date' => $start->copy()->addHours($hours),
                'description' => "<p>{$description}</p>",
                'ticket_url' => $ticketUrl,
            ]);
        }
    }

    private function seedSocialHighlights(Station $station): void
    {
        $highlights = [
            [SocialPlatform::YouTube, 'https://www.youtube.com/watch?v=OZSAMPLE001', 'Live Session: Night Session Unplugged', true],
            [SocialPlatform::TikTok, 'https://www.tiktok.com/@ozradiobandung/video/7400000000000000001', 'Tebak Lagu dari Intro Bareng Raka', true],
            [SocialPlatform::Instagram, 'https://www.instagram.com/p/OZSAMPLE01/', 'Behind the Scenes OZ Breakfast Club', true],
            [SocialPlatform::YouTube, 'https://www.youtube.com/watch?v=OZSAMPLE002', 'OZ Top 40 Countdown Recap', false],
            [SocialPlatform::TikTok, 'https://www.tiktok.com/@ozradiobandung/video/7400000000000000002', 'Macet Pasteur? Ini Tips dari Dimas', false],
            [SocialPlatform::Instagram, 'https://www.instagram.com/p/OZSAMPLE02/', 'Recap OZ Charity Night Tahun Lalu', false],
        ];

        foreach ($highlights as $index => [$platform, $url, $title, $featured]) {
            $station->socialHighlights()->create([
                'platform' => $platform,
                'post_url' => $url,
                'title' => $title,
                'is_featured' => $featured,
                'sort_order' => $index,
            ]);
        }
    }
}
