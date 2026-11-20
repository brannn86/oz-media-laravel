<?php

use App\Enums\AdminRole;
use App\Enums\PointReason;
use App\Enums\PollStatus;
use App\Enums\RedemptionStatus;
use App\Enums\SongRequestStatus;
use App\Events\Live\PollUpdated;
use App\Events\Live\SongRequestUpdated;
use App\Filament\Pages\StudioMode;
use App\Filament\Resources\RewardRedemptions\Pages\ManageRewardRedemptions;
use App\Filament\Resources\RewardRedemptions\RewardRedemptionResource;
use App\Filament\Resources\Rewards\RewardResource;
use App\Models\PointTransaction;
use App\Models\Poll;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([SongRequestUpdated::class, PollUpdated::class]);
    config(['broadcasting.default' => 'null']);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
});

function staff(AdminRole $role): User
{
    return User::factory()->create()->assignRole($role->value);
}

describe('studio mode', function () {
    it('lets the on-air team approve, play and reject requests', function () {
        $this->actingAs(staff(AdminRole::Editorial));
        $listener = User::factory()->create();
        [$a, $b] = SongRequest::factory()->for($this->station)->for($listener)->count(2)->create();

        Livewire::test(StudioMode::class)
            ->assertSee($a->song_title)
            ->callAction(TestAction::make('approveRequest')->arguments(['request' => $a->id]))
            ->callAction(TestAction::make('playRequest')->arguments(['request' => $a->id]))
            ->callAction(TestAction::make('rejectRequest')->arguments(['request' => $b->id]))
            ->assertDontSee($a->song_title);

        expect($a->refresh()->status)->toBe(SongRequestStatus::Played)
            ->and($b->refresh()->status)->toBe(SongRequestStatus::Rejected)
            ->and($listener->refresh()->points_balance)->toBe(25);
    });

    it('launches a trivia poll and closes it', function () {
        $this->actingAs(staff(AdminRole::SocialMedia));

        Livewire::test(StudioMode::class)
            ->callAction('launchPoll', data: [
                'type' => 'trivia',
                'question' => 'Which year did OZ start?',
                // A simple repeater's internal state: one ['label' => ...] per answer.
                'options' => [['label' => '1971'], ['label' => '1990']],
                'correct' => 0,
            ])
            ->assertHasNoActionErrors()
            ->assertSee('Which year did OZ start?')
            ->callAction('closePoll');

        $poll = Poll::query()->with('options')->firstOrFail();

        expect($poll->status)->toBe(PollStatus::Closed)
            ->and($poll->options->firstWhere('label', '1971')->is_correct)->toBeTrue()
            ->and($poll->options->firstWhere('label', '1990')->is_correct)->toBeFalse();
        Event::assertDispatched(PollUpdated::class);

    });

    it('listens for requests and polls over Reverb', function () {
        $this->actingAs(staff(AdminRole::Dev));

        expect(array_keys(Livewire::test(StudioMode::class)->instance()->getListeners()))
            ->toContain('echo:station.oz-bandung.requests,.song-request.updated', 'echo:station.oz-bandung.polls,.poll.updated');
    });
});

describe('rewards admin', function () {
    it('is for dev and social media, not editorial', function (AdminRole $role, int $status) {
        $this->actingAs(staff($role));

        $this->get(RewardResource::getUrl())->assertStatus($status);
        $this->get(RewardRedemptionResource::getUrl())->assertStatus($status);
    })->with([
        'dev' => [AdminRole::Dev, 200],
        'social media' => [AdminRole::SocialMedia, 200],
        'editorial' => [AdminRole::Editorial, 403],
    ]);

    it('hands over or cancels redemptions, refunding on cancel', function () {
        $this->actingAs(staff(AdminRole::SocialMedia));
        $listener = User::factory()->create();
        $reward = Reward::factory()->limited(3)->create(['points_cost' => 100]);
        $handed = RewardRedemption::create(['reward_id' => $reward->id, 'user_id' => $listener->id, 'points_spent' => 100]);
        $cancelled = RewardRedemption::create(['reward_id' => $reward->id, 'user_id' => $listener->id, 'points_spent' => 100]);

        Livewire::test(ManageRewardRedemptions::class)
            ->assertCanSeeTableRecords([$handed, $cancelled])
            ->callAction(TestAction::make('fulfill')->table($handed), data: ['admin_note' => 'Collected at studio'])
            ->callAction(TestAction::make('cancel')->table($cancelled), data: ['admin_note' => 'Out of size L']);

        expect($handed->refresh()->status)->toBe(RedemptionStatus::Fulfilled)
            ->and($handed->admin_note)->toBe('Collected at studio')
            ->and($cancelled->refresh()->status)->toBe(RedemptionStatus::Cancelled)
            ->and($listener->refresh()->points_balance)->toBe(100)
            ->and($reward->refresh()->stock)->toBe(4)
            ->and(PointTransaction::where('reason', PointReason::RedemptionRefund)->count())->toBe(1);
    });
});

it('renders the public OZ Poin page with the signed-in balance', function () {
    Reward::factory()->create(['name' => 'OZ Tumbler']);
    $listener = User::factory()->create();
    $listener->forceFill(['points_balance' => 42, 'current_streak_days' => 3])->save();

    $this->get('/oz-bandung/rewards')->assertInertia(fn (Assert $page) => $page
        ->component('station/rewards')
        ->where('account', null)
        ->where('rewards.0.name', 'OZ Tumbler')
        ->has('leaderboard.week')
        ->where('earning.poll_vote', 5));

    $this->actingAs($listener)->get('/oz-bandung/rewards')->assertInertia(fn (Assert $page) => $page
        ->where('account.points_balance', 42)
        ->where('account.current_streak_days', 3));
});
