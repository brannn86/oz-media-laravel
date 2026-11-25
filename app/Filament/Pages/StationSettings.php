<?php

namespace App\Filament\Pages;

use App\Enums\StationSiteType;
use App\Enums\StreamTitleFormat;
use App\Events\StationStreamStatusUpdated;
use App\Filament\NavigationGroup;
use App\Http\Resources\StationResource;
use App\Models\Station;
use App\Support\StationCache;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Edits one station's public-facing settings. Switch station with ?station=<slug>.
 *
 * @property-read Schema $form
 */
class StationSettings extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Station;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'station-settings';

    #[Url(as: 'station')]
    public ?string $stationSlug = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    #[Locked]
    public Station $station;

    public function mount(): void
    {
        $this->station = Station::query()
            ->when($this->stationSlug, fn ($query, string $slug) => $query->where('slug', $slug))
            ->orderBy('id')
            ->firstOrFail();

        $this->stationSlug = $this->station->slug;

        $this->form->fill($this->station->attributesToArray());
    }

    public function getTitle(): string|Htmlable
    {
        // Shield resolves page labels without mounting the page.
        return isset($this->station) ? "{$this->station->name} Settings" : 'Station Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Station Settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->station)
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->required()
                                    ->alphaDash()
                                    ->maxLength(255)
                                    ->unique(Station::class, 'slug', ignorable: $this->station)
                                    ->helperText('Changing this changes the public URL.'),
                                TextInput::make('tagline')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Select::make('site_type')
                                    ->label('Website')
                                    ->options(StationSiteType::class)
                                    ->required()
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->helperText('Basic is one page with the radio player (and the privacy policy); programs, news, events, chat and video are switched off.'),
                                Toggle::make('is_active')
                                    ->label('Station is active')
                                    ->helperText('Inactive stations are hidden from the public site.'),
                                Toggle::make('rewards_page_enabled')
                                    ->label('Show the OZ Poin page')
                                    ->helperText('Off hides the OZ Poin page and its links. Listeners keep earning points, so nothing is lost when you switch it back on.')
                                    ->hidden(fn (Get $get): bool => $this->isBasic($get)),
                            ]),
                        Tab::make('Streaming')
                            ->icon(Heroicon::OutlinedSignal)
                            ->schema([
                                TextInput::make('stream_audio_url')
                                    ->label('Audio stream URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://stream.example.com/oz-bandung.mp3')
                                    ->helperText('Icecast / Shoutcast / HLS audio endpoint used in Audio Only mode. For an Icecast mount, the song on air is read from the server\'s status page.'),
                                Select::make('stream_title_format')
                                    ->label('Song title format')
                                    ->options(StreamTitleFormat::class)
                                    ->required()
                                    ->selectablePlaceholder(false)
                                    ->helperText('How the automation system writes the stream title, e.g. "Hatchu!! - Salma Salsabil" is Title - Artist.'),
                                TextInput::make('stream_video_url')
                                    ->hidden(fn (Get $get): bool => $this->isBasic($get))
                                    ->label('Video stream URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://www.youtube.com/embed/live_stream?channel=…')
                                    ->helperText("YouTube link (a video, or live_stream?channel=… for the channel's current livestream), an HLS .m3u8 URL, or an embeddable page. While it's set, listeners can choose to watch video; leave it empty for radio only."),
                                SpatieMediaLibraryFileUpload::make('intermission')
                                    ->hidden(fn (Get $get): bool => $this->isBasic($get))
                                    ->label('Intermission video')
                                    ->collection('intermission')
                                    ->acceptedFileTypes(Station::INTERMISSION_MIME_TYPES)
                                    ->maxSize(20480)
                                    ->helperText('MP4 or WebM, up to 20 MB. Loops in the video player whenever the livestream is blank or offline (a show starting late, or not live on YouTube yet). Keep it short and light.'),
                            ]),
                        Tab::make('Contact')
                            ->icon(Heroicon::OutlinedPhone)
                            ->columns(2)
                            ->schema([
                                TextInput::make('contact_info.heading')
                                    ->label('Heading')
                                    ->maxLength(120)
                                    ->placeholder(StationResource::DEFAULT_CONTACT_HEADING)
                                    ->helperText('The big line above the contact details on the station home page. Leave empty for the default.')
                                    ->columnSpanFull(),
                                TextInput::make('contact_info.email')
                                    ->label('Email')
                                    ->email()
                                    ->prefixIcon(Heroicon::OutlinedEnvelope),
                                TextInput::make('contact_info.whatsapp')
                                    ->label('WhatsApp')
                                    ->tel()
                                    ->prefixIcon(Heroicon::OutlinedChatBubbleLeftRight)
                                    ->helperText('International format, e.g. +6281234567890.'),
                                Textarea::make('contact_info.address')
                                    ->label('Address')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make('contact_info.map_url')
                                    ->label('Map link')
                                    ->url()
                                    ->rule('url:http,https')
                                    ->maxLength(2048)
                                    ->prefixIcon(Heroicon::OutlinedMapPin)
                                    ->placeholder('https://maps.app.goo.gl/…')
                                    ->helperText('Google Maps (or any map) link to the studio. The address on the site links here.')
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Sponsor')
                            ->icon(Heroicon::OutlinedMegaphone)
                            ->columns(2)
                            ->schema([
                                Toggle::make('sponsor_enabled')
                                    ->label('Show the ad space')
                                    ->helperText('Off removes the space under the player completely: no banner, no "Your brand here" house ad. The banner below is kept for when you switch it back on.')
                                    ->live()
                                    ->columnSpanFull(),
                                SpatieMediaLibraryFileUpload::make('sponsor_banner')
                                    ->label('Banner')
                                    ->collection('sponsor_banner')
                                    ->image()
                                    ->maxSize(4096)
                                    ->helperText(sprintf(
                                        'Shown full width under the player. Upload %d × %d px (8:1), JPG, PNG or WebP, up to 4 MB; it shows at about 1232 × 154 on a wide screen. Other shapes are cropped to fit, so keep text away from the edges, and large: it shrinks on smaller screens. Leave empty to show the "Your brand here" house ad.',
                                        ...Station::SPONSOR_BANNERS['sponsor_banner'],
                                    ))
                                    ->columnSpanFull(),
                                SpatieMediaLibraryFileUpload::make('sponsor_banner_mobile')
                                    ->label('Phone banner (optional)')
                                    ->collection('sponsor_banner_mobile')
                                    ->image()
                                    ->maxSize(4096)
                                    ->helperText(sprintf(
                                        'Used instead on phones. Upload %d × %d px (4:1). Without it, phones get the wide banner, scaled down. Other shapes are cropped to fit.',
                                        ...Station::SPONSOR_BANNERS['sponsor_banner_mobile'],
                                    ))
                                    ->columnSpanFull(),
                                TextInput::make('sponsor_name')
                                    ->label('Sponsor name')
                                    ->maxLength(255)
                                    ->helperText('Read out by screen readers in place of the image.'),
                                TextInput::make('sponsor_url')
                                    ->label('Link')
                                    ->url()
                                    ->rule('url:http,https')
                                    ->maxLength(2048)
                                    ->prefixIcon(Heroicon::OutlinedLink)
                                    ->placeholder('https://…')
                                    ->helperText('Where a click on the banner goes (opens in a new tab).'),
                            ]),
                        Tab::make('Social')
                            ->icon(Heroicon::OutlinedShare)
                            ->columns(2)
                            ->schema([
                                TextInput::make('social_links.instagram')->label('Instagram')->url()->placeholder('https://www.instagram.com/…'),
                                TextInput::make('social_links.tiktok')->label('TikTok')->url()->placeholder('https://www.tiktok.com/@…'),
                                TextInput::make('social_links.youtube')->label('YouTube')->url()->placeholder('https://www.youtube.com/@…'),
                                TextInput::make('social_links.threads')->label('Threads')->url()->placeholder('https://www.threads.com/@…'),
                                TextInput::make('social_links.x')->label('X (Twitter)')->url()->placeholder('https://x.com/…')
                                    ->helperText('Optional. Left empty, X is hidden on the site.'),
                            ]),
                        Tab::make('About')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                RichEditor::make('about_content')
                                    ->hiddenLabel()
                                    ->fileAttachmentsDirectory('stations/about'),
                            ]),
                        Tab::make('Privacy Policy')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                RichEditor::make('privacy_policy_content')
                                    ->hiddenLabel(),
                            ]),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save settings')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $intermissionBefore = $this->station->getFirstMedia('intermission')?->getKey();

        $this->station->update($this->form->getState());
        $this->form->saveRelationships();

        // Uploads (banner, intermission) don't touch the station row, so the
        // observer misses them; the public site caches both.
        StationCache::flush($this->station);

        if ($this->station->refresh()->getFirstMedia('intermission')?->getKey() !== $intermissionBefore) {
            // Open players pick up the new clip without a reload.
            StationStreamStatusUpdated::dispatch($this->station);
        }

        if ($this->station->wasChanged('slug')) {
            // Keep the URL in sync so a refresh doesn't 404.
            $this->stationSlug = $this->station->slug;
        }

        Notification::make()
            ->title('Station settings saved')
            ->success()
            ->send();
    }

    /** The form, as currently filled in, is for a basic (player only) site. */
    private function isBasic(Get $get): bool
    {
        $type = $get('site_type');

        return ($type instanceof StationSiteType ? $type : StationSiteType::tryFrom((string) $type)) === StationSiteType::Basic;
    }

    protected function getHeaderActions(): array
    {
        $others = Station::query()->whereKeyNot($this->station->getKey())->orderBy('name')->get();

        return [
            ...($others->isEmpty() ? [] : [
                ActionGroup::make($others->map(fn (Station $station): Action => Action::make("switch-{$station->id}")
                    ->label($station->name)
                    ->url(static::getUrl(['station' => $station->slug])))->all())
                    ->label('Switch station')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->button()
                    ->color('gray'),
            ]),
        ];
    }
}
