<?php

use App\Models\Station;

function addMeasurementToPolicies(): void
{
    (require database_path('migrations/2026_11_25_130000_add_measurement_to_privacy_policies.php'))->up();
}

it('adds the measurement text once, after the existing policy', function () {
    $station = Station::factory()->create(['privacy_policy_content' => '<p>Our policy.</p>']);

    addMeasurementToPolicies();
    addMeasurementToPolicies();

    $policy = $station->fresh()->privacy_policy_content;

    expect($policy)->toStartWith('<p>Our policy.</p>')
        ->and(substr_count($policy, 'What this site measures'))->toBe(1);
});
