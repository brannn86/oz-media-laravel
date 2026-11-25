<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\NavigationGroup;
use App\Models\Event;
use App\Models\Post;
use App\Models\Station;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Builds links with UTM tags, so Analytics > Audience can tell which post
 * or campaign brought listeners in.
 *
 * @property-read Schema $form
 */
class CampaignLinks extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Analytics;

    protected static ?string $navigationLabel = 'Campaign links';

    protected static ?string $title = 'Campaign links';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'analytics/campaign-links';

    /** Where links usually go, as utm_source values. */
    public const SOURCES = [
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
        'x' => 'X',
        'threads' => 'Threads',
        'whatsapp' => 'WhatsApp',
        'newsletter' => 'Newsletter',
        'poster' => 'Poster / QR code',
    ];

    public const MEDIUMS = [
        'bio' => 'Bio link',
        'post' => 'Post',
        'story' => 'Story',
        'reel' => 'Reel / short video',
        'video' => 'Video description',
        'broadcast' => 'Broadcast / channel',
        'ad' => 'Paid ad',
    ];

    private const PAGES = [
        'home' => 'Home',
        'news' => 'OZ News',
        'article' => 'An article',
        'events' => 'Events',
        'event' => 'An event',
        'programs' => 'Programs & schedule',
        'announcers' => 'Announcers',
        'rewards' => 'OZ Poin',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'station' => Station::query()->active()->orderBy('id')->value('id'),
            'page' => 'home',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Where the link goes')
                    ->columns(2)
                    ->schema([
                        Select::make('station')
                            ->options(fn (): array => Station::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('page')
                            ->options(self::PAGES)
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('article')
                            ->options(fn (Get $get): array => Post::query()
                                ->where('station_id', $get('station'))
                                ->published()
                                ->latest('published_at')
                                ->limit(100)
                                ->pluck('title', 'slug')
                                ->all())
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('page') === 'article')
                            ->columnSpanFull(),
                        Select::make('event')
                            ->options(fn (Get $get): array => Event::query()
                                ->where('station_id', $get('station'))
                                ->orderByDesc('start_date')
                                ->limit(100)
                                ->pluck('title', 'slug')
                                ->all())
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('page') === 'event')
                            ->columnSpanFull(),
                    ]),
                Section::make('Where you share it')
                    ->description('Use the same words every time, so each source and campaign adds up in one row.')
                    ->columns(3)
                    ->schema([
                        Select::make('source')
                            ->label('Source (utm_source)')
                            ->options(self::SOURCES)
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->helperText('The site or app the link is on.'),
                        Select::make('medium')
                            ->label('Placement (utm_medium)')
                            ->options(self::MEDIUMS)
                            ->native(false)
                            ->live()
                            ->helperText('Where on it.'),
                        TextInput::make('campaign')
                            ->label('Campaign (utm_campaign)')
                            ->placeholder('konser-amal')
                            ->maxLength(80)
                            ->live(onBlur: true)
                            ->helperText('What you are promoting.'),
                    ]),
                Grid::make(1)->schema([
                    TextEntry::make('link')
                        ->label('Your link')
                        ->state(fn (Get $get): string => $this->link(
                            $get('station'), $get('page'), $get('article'), $get('event'),
                            $get('source'), $get('medium'), $get('campaign'),
                        ) ?? 'Pick a page (and the article or event) to get a link.')
                        ->copyable(fn (Get $get): bool => $this->link($get('station'), $get('page'), $get('article'), $get('event'), $get('source'), $get('medium'), $get('campaign')) !== null)
                        ->copyMessage('Link copied')
                        ->fontFamily('mono')
                        ->helperText('Shorten it with a link shortener if it looks long in a caption; the tags still arrive. Results appear in Analytics > Audience.'),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form'),
        ]);
    }

    /**
     * The page's address with UTM tags, or null until a page is chosen.
     */
    public function link(mixed $stationId, mixed $page, mixed $article, mixed $event, mixed $source, mixed $medium, mixed $campaign): ?string
    {
        $station = Station::query()->find($stationId);

        if (! $station instanceof Station) {
            return null;
        }

        $url = match ($page) {
            'home' => route('station.home', $station),
            'news' => route('station.news.index', $station),
            'article' => filled($article) ? route('station.news.show', [$station, (string) $article]) : null,
            'events' => route('station.events', $station),
            'event' => filled($event) ? route('station.events.show', [$station, (string) $event]) : null,
            'programs' => route('station.programs', $station),
            'announcers' => route('station.announcers', $station),
            'rewards' => route('station.rewards', $station),
            default => null,
        };

        if ($url === null) {
            return null;
        }

        $tags = array_filter([
            'utm_source' => Str::slug((string) $source),
            'utm_medium' => Str::slug((string) $medium),
            'utm_campaign' => Str::slug((string) $campaign),
        ]);

        return $tags === [] ? $url : $url.'?'.http_build_query($tags);
    }
}
