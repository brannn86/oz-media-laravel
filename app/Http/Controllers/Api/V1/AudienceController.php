<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Analytics\RecordAudience;
use App\Enums\AudienceMode;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Support\ViewerKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * "Still here": a player pings once a minute while the radio plays or the
 * video is open. Anyone can ping (guests included); a visitor counts once a
 * minute however often they call.
 */
class AudienceController extends Controller
{
    public function __invoke(Request $request, Station $station, RecordAudience $audience): Response
    {
        $data = $request->validate(['mode' => ['required', Rule::enum(AudienceMode::class)]]);

        $audience->ping($station, AudienceMode::from($data['mode']), ViewerKey::from($request));

        return response()->noContent();
    }
}
