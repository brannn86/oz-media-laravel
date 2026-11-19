<?php

namespace App\Models;

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

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $tagline
 * @property string|null $stream_audio_url
 * @property string|null $stream_video_url
 * @property bool $is_video_live
 * @property array{email?: string|null, phone?: string|null, address?: string|null, whatsapp?: string|null}|null $contact_info
 * @property array{youtube?: string|null, tiktok?: string|null, instagram?: string|null, x?: string|null}|null $social_links
 * @property string|null $about_content
 * @property string|null $privacy_policy_content
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(StationObserver::class)]
#[Fillable([
    'slug', 'name', 'tagline', 'stream_audio_url', 'stream_video_url', 'is_video_live',
    'contact_info', 'social_links', 'about_content', 'privacy_policy_content', 'is_active',
])]
class Station extends Model
{
    /** @use HasFactory<StationFactory> */
    use HasFactory, HasUniqueSlug;

    protected function casts(): array
    {
        return [
            'is_video_live' => 'boolean',
            'is_active' => 'boolean',
            'contact_info' => 'array',
            'social_links' => 'array',
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
     * The stream the public player should use right now.
     */
    public function activeStreamUrl(): ?string
    {
        return $this->is_video_live && filled($this->stream_video_url)
            ? $this->stream_video_url
            : $this->stream_audio_url;
    }
}
