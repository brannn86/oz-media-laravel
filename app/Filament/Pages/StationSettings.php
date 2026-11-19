<?php

namespace App\Filament\Pages;

use App\Filament\Actions\ToggleStreamModeAction;
use App\Filament\NavigationGroup;
use App\Models\Station;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\RichEditor;
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
                                Toggle::make('is_active')
                                    ->label('Station is active')
                                    ->helperText('Inactive stations are hidden from the public site.'),
                            ]),
                        Tab::make('Streaming')
                            ->icon(Heroicon::OutlinedSignal)
                            ->schema([
                                TextInput::make('stream_audio_url')
                                    ->label('Audio stream URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://stream.example.com/oz-bandung.mp3')
                                    ->helperText('Icecast / Shoutcast / HLS audio endpoint used in Audio Only mode.'),
                                TextInput::make('stream_video_url')
                                    ->label('Video stream URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://www.youtube.com/embed/live_stream?channel=…')
                                    ->helperText('Embed or HLS URL used when the station is in Video Livestream mode. Switch modes with the button at the top of the page.'),
                            ]),
                        Tab::make('Contact')
                            ->icon(Heroicon::OutlinedPhone)
                            ->columns(2)
                            ->schema([
                                TextInput::make('contact_info.email')
                                    ->label('Email')
                                    ->email()
                                    ->prefixIcon(Heroicon::OutlinedEnvelope),
                                TextInput::make('contact_info.phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->prefixIcon(Heroicon::OutlinedPhone),
                                TextInput::make('contact_info.whatsapp')
                                    ->label('WhatsApp')
                                    ->tel()
                                    ->prefixIcon(Heroicon::OutlinedChatBubbleLeftRight)
                                    ->helperText('International format, e.g. +6281234567890.'),
                                Textarea::make('contact_info.address')
                                    ->label('Address')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Social')
                            ->icon(Heroicon::OutlinedShare)
                            ->columns(2)
                            ->schema([
                                TextInput::make('social_links.youtube')->label('YouTube')->url()->placeholder('https://www.youtube.com/@…'),
                                TextInput::make('social_links.tiktok')->label('TikTok')->url()->placeholder('https://www.tiktok.com/@…'),
                                TextInput::make('social_links.instagram')->label('Instagram')->url()->placeholder('https://www.instagram.com/…'),
                                TextInput::make('social_links.x')->label('X')->url()->placeholder('https://x.com/…'),
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
        $this->station->update($this->form->getState());

        if ($this->station->wasChanged('slug')) {
            // Keep the URL in sync so a refresh doesn't 404.
            $this->stationSlug = $this->station->slug;
        }

        Notification::make()
            ->title('Station settings saved')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $others = Station::query()->whereKeyNot($this->station->getKey())->orderBy('name')->get();

        return [
            ToggleStreamModeAction::make($this->station),
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
