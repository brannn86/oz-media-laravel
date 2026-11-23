<?php

namespace App\Http\Resources;

use App\Models\TrackPlay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Song on air (null between songs or when the stream is off) and recently played, newest first.
 *
 * @property array{track: TrackPlay|null, recent: Collection<int, TrackPlay>} $resource
 */
class NowPlayingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'track' => $this->resource['track'] ? TrackPlayResource::make($this->resource['track'])->resolve($request) : null,
            'recent' => TrackPlayResource::collection($this->resource['recent'])->resolve($request),
        ];
    }
}
