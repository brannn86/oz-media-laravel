<?php

namespace App\Models;

use App\Enums\StationSiteType;
use App\Enums\StreamTitleFormat;
use App\Models\Concerns\HasUniqueSlug;
use App\Observers\StationObserver;
use Carbon\CarbonImmutable;
use Database\Factories\StationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $tagline
 * @property StationSiteType $site_type
 * @property string|null $stream_audio_url
 * @property string|null $stream_video_url
 * @property StreamTitleFormat $stream_title_format
 * @property int $chat_slow_mode_seconds Minimum seconds between messages per listener (0 = off).
 * @property array{heading?: string|null, email?: string|null, phone?: string|null, address?: string|null, map_url?: string|null, whatsapp?: string|null}|null $contact_info
 * @property array{instagram?: string|null, tiktok?: string|null, youtube?: string|null, threads?: string|null, x?: string|null}|null $social_links
 * @property string|null $about_content
 * @property string|null $privacy_policy_content
 * @property string|null $sponsor_name
 * @property string|null $sponsor_url
 * @property bool $sponsor_enabled Off: no ad space under the player at all.
 * @property bool $rewards_page_enabled Off: the OZ Poin page is hidden (points still accrue).
 * @property bool $ozubathon_enabled The Ozubathon (55th-anniversary subathon) theme is on.
 * @property CarbonImmutable|null $ozubathon_starts_at When its 55 hours start; null: config('oz.ozubathon.default_starts_at').
 * @property list<string>|null $ozubathon_ticker The rolling text under the hero; null: config('oz.ozubathon.ticker').
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(StationObserver::class)]
#[Fillable([
    'slug', 'name', 'tagline', 'site_type', 'stream_audio_url', 'stream_video_url', 'stream_title_format', 'chat_slow_mode_seconds',
    'contact_info', 'social_links', 'about_content', 'privacy_policy_content', 'sponsor_name', 'sponsor_url', 'sponsor_enabled', 'rewards_page_enabled', 'ozubathon_enabled', 'ozubathon_starts_at', 'ozubathon_ticker', 'is_active',
])]
class Station extends Model implements HasMedia
{
    /** @use HasFactory<StationFactory> */
    use HasFactory, HasUniqueSlug, InteractsWithMedia;

    /** Video files the intermission clip may be. */
    public const INTERMISSION_MIME_TYPES = ['video/mp4', 'video/webm'];

    /**
     * Sponsor banner collections and their recommended upload size (2x the
     * size on screen: 1232×154 across a wide page, 4:1 on a phone). Larger
     * uploads are scaled down to this.
     */
    public const SPONSOR_BANNERS = [
        'sponsor_banner' => [2400, 300],
        'sponsor_banner_mobile' => [1200, 300],
    ];

    protected $attributes = [
        'stream_title_format' => 'title_artist',
        'site_type' => 'full',
        'sponsor_enabled' => true,
        'rewards_page_enabled' => true,
        'ozubathon_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'stream_title_format' => StreamTitleFormat::class,
            'site_type' => StationSiteType::class,
            'is_active' => 'boolean',
            'sponsor_enabled' => 'boolean',
            'rewards_page_enabled' => 'boolean',
            'ozubathon_enabled' => 'boolean',
            'ozubathon_starts_at' => 'immutable_datetime',
            'ozubathon_ticker' => 'array',
            'chat_slow_mode_seconds' => 'integer',
            'contact_info' => 'array',
            'social_links' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        // Plays in the video player whenever the livestream is blank or offline.
        $this->addMediaCollection('intermission')
            ->singleFile()
            ->acceptsMimeTypes(self::INTERMISSION_MIME_TYPES);

        // The sponsor banner under the player: wide, plus an optional phone crop.
        foreach (array_keys(self::SPONSOR_BANNERS) as $collection) {
            $this->addMediaCollection($collection)
                ->singleFile()
                ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (self::SPONSOR_BANNERS as $collection => [$width, $height]) {
            $this->addMediaConversion('webp')
                ->performOnCollections($collection)
                ->format('webp')
                ->quality(85)
                ->fit(Fit::Max, $width, $height);
        }
    }

    public function isBasic(): bool
    {
        return $this->site_type === StationSiteType::Basic;
    }

    /**
     * The Ozubathon while its theme is on: when its 55 hours start and end.
     * Null when the theme is off (or the station is a basic, player-only site).
     *
     * @return array{starts_at: string, ends_at: string, hours: int, ticker: list<string>}|null
     */
    public function ozubathon(): ?array
    {
        if (! $this->ozubathon_enabled || $this->isBasic()) {
            return null;
        }

        $hours = (int) config('oz.ozubathon.hours');
        $start = $this->ozubathon_starts_at
            ?? CarbonImmutable::parse((string) config('oz.ozubathon.default_starts_at'), 'Asia/Jakarta');

        return [
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $start->addHours($hours)->toIso8601String(),
            'hours' => $hours,
            'ticker' => array_values(array_filter($this->ozubathon_ticker ?: config('oz.ozubathon.ticker'), 'filled')),
        ];
    }

    /**
     * The uploaded sponsor banner, or null for the house ad.
     *
     * @return array{name: string|null, url: string|null, image_url: string, mobile_image_url: string|null}|null
     */
    public function sponsor(): ?array
    {
        $banner = $this->getFirstMedia('sponsor_banner');

        if (! $banner instanceof Media) {
            return null;
        }

        return [
            'name' => $this->sponsor_name,
            'url' => $this->sponsor_url,
            'image_url' => $banner->getAvailableUrl(['webp']),
            'mobile_image_url' => $this->getFirstMedia('sponsor_banner_mobile')?->getAvailableUrl(['webp']),
        ];
    }

    protected function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Program, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * @return HasManyThrough<ProgramSchedule, Program, $this>
     */
    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(ProgramSchedule::class, Program::class);
    }

    /**
     * @return HasMany<Announcer, $this>
     */
    public function announcers(): HasMany
    {
        return $this->hasMany(Announcer::class);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Reverb channel carrying this station's live chat (public: anyone can read).
     */
    public function chatChannel(): string
    {
        return "station.{$this->slug}.chat";
    }

    /**
     * @return HasMany<SongRequest, $this>
     */
    public function songRequests(): HasMany
    {
        return $this->hasMany(SongRequest::class);
    }

    /**
     * @return HasMany<Poll, $this>
     */
    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    public function requestsChannel(): string
    {
        return "station.{$this->slug}.requests";
    }

    public function pollsChannel(): string
    {
        return "station.{$this->slug}.polls";
    }

    public function reactionsChannel(): string
    {
        return "station.{$this->slug}.reactions";
    }

    /**
     * Presence channel listeners join while the player is on; its size is the live listener count.
     */
    public function listenersChannel(): string
    {
        return "station.{$this->slug}.listeners";
    }

    /**
     * @return HasMany<TrackPlay, $this>
     */
    public function trackPlays(): HasMany
    {
        return $this->hasMany(TrackPlay::class);
    }

    /**
     * Icecast's status-json.xsl on the audio stream's server, and the stream's
     * mount ("/ozbandung"). Null when the audio stream isn't an Icecast mount (HLS).
     *
     * @return array{url: string, mount: string}|null
     */
    public function icecastStatusEndpoint(): ?array
    {
        $parts = filled($this->stream_audio_url) ? parse_url($this->stream_audio_url) : false;

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'], $parts['path'])
            || ! in_array($parts['scheme'], ['http', 'https'], true)
            || $parts['path'] === '/' || str_ends_with(strtolower($parts['path']), '.m3u8')) {
            return null;
        }

        $port = isset($parts['port']) ? ":{$parts['port']}" : '';

        return [
            'url' => "{$parts['scheme']}://{$parts['host']}{$port}/status-json.xsl",
            'mount' => rtrim($parts['path'], '/'),
        ];
    }

    /**
     * @return HasMany<SocialHighlight, $this>
     */
    public function socialHighlights(): HasMany
    {
        return $this->hasMany(SocialHighlight::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Video is offered whenever a video stream URL is set; listeners choose
     * radio or video themselves.
     */
    public function hasVideoStream(): bool
    {
        return filled($this->stream_video_url);
    }

    public function intermissionVideoUrl(): ?string
    {
        return $this->getFirstMediaUrl('intermission') ?: null;
    }
}
