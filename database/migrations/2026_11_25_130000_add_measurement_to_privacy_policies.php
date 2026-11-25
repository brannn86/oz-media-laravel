<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "What this site measures" text to each station's privacy policy,
 * where admins can edit it like the rest of the policy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $text = (string) file_get_contents(database_path('content/privacy-measurement.html'));

        foreach (DB::table('stations')->get(['id', 'privacy_policy_content']) as $station) {
            $policy = (string) $station->privacy_policy_content;

            if (! str_contains($policy, 'What this site measures')) {
                DB::table('stations')->where('id', $station->id)->update([
                    'privacy_policy_content' => trim($policy."\n".$text),
                ]);
            }
        }
    }
};
