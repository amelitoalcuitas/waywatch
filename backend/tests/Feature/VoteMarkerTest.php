<?php

namespace Tests\Feature;

use App\Models\Marker;
use App\Models\MarkerVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoteMarkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_vote_on_marker(): void
    {
        $marker = $this->createMarker();

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ])->assertUnauthorized();
    }

    public function test_first_vote_increments_counter_and_creates_vote(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ])->assertOk()
            ->assertJsonPath('data.likes', 1)
            ->assertJsonPath('data.dislikes', 0)
            ->assertJsonPath('user_vote_type', MarkerVote::VOTE_STILL_THERE);

        $this->assertDatabaseHas('marker_votes', [
            'marker_id' => $marker->id,
            'user_id' => $user->id,
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ]);
    }

    public function test_switching_vote_updates_counts_and_keeps_single_vote_row(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ])->assertOk();

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_NOT_THERE,
        ])->assertOk()
            ->assertJsonPath('data.likes', 0)
            ->assertJsonPath('data.dislikes', 1)
            ->assertJsonPath('user_vote_type', MarkerVote::VOTE_NOT_THERE);

        $this->assertDatabaseCount('marker_votes', 1);

        $this->assertDatabaseHas('marker_votes', [
            'marker_id' => $marker->id,
            'user_id' => $user->id,
            'vote_type' => MarkerVote::VOTE_NOT_THERE,
        ]);
    }

    public function test_repeating_same_vote_toggles_vote_off(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ])->assertOk();

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ])->assertOk()
            ->assertJsonPath('data.likes', 0)
            ->assertJsonPath('data.dislikes', 0)
            ->assertJsonPath('user_vote_type', null);

        $this->assertDatabaseMissing('marker_votes', [
            'marker_id' => $marker->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_index_returns_authenticated_users_vote_type_for_marker(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        MarkerVote::create([
            'marker_id' => $marker->id,
            'user_id' => $user->id,
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/markers?latitude=14.5995&longitude=120.9842&radius=3&start_date=2000-01-01&end_date=2100-01-01')
            ->assertOk()
            ->assertJsonPath('data.0.id', $marker->id)
            ->assertJsonPath('data.0.user_vote_type', MarkerVote::VOTE_STILL_THERE);
    }

    public function test_old_vote_values_are_rejected_after_cutover(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => 'like',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['vote_type']);

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => 'dislike',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['vote_type']);
    }

    public function test_index_returns_null_vote_type_for_guest(): void
    {
        $marker = $this->createMarker();

        $this->getJson('/api/markers?latitude=14.5995&longitude=120.9842&radius=3&start_date=2000-01-01&end_date=2100-01-01')
            ->assertOk()
            ->assertJsonPath('data.0.id', $marker->id)
            ->assertJsonPath('data.0.user_vote_type', null);
    }

    public function test_still_there_vote_extends_marker_expiry(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();
        $marker->forceFill([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
            'expires_at' => now()->addHours(4),
            'base_expires_at' => now()->addHours(4),
            'max_expires_at' => now()->addDays(7),
        ])->saveQuietly();

        $before = $marker->expires_at->copy();
        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ])->assertOk();

        $marker->refresh();
        $this->assertTrue($marker->expires_at->gt($before));
    }

    public function test_not_there_vote_reduces_marker_expiry_after_grace_window(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();
        $marker->forceFill([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
            'expires_at' => now()->addHours(6),
            'base_expires_at' => now()->addHours(6),
            'max_expires_at' => now()->addDays(7),
        ])->saveQuietly();

        $before = $marker->expires_at->copy();
        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/vote", [
            'vote_type' => MarkerVote::VOTE_NOT_THERE,
        ])->assertOk();

        $marker->refresh();
        $this->assertTrue($marker->expires_at->lt($before));
    }

    public function test_not_there_quorum_triggers_early_expiry(): void
    {
        $marker = $this->createMarker();
        $marker->forceFill([
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
            'expires_at' => now()->addDays(2),
            'base_expires_at' => now()->addDays(2),
            'max_expires_at' => now()->addDays(7),
        ])->saveQuietly();

        $voteTypes = [
            MarkerVote::VOTE_NOT_THERE,
            MarkerVote::VOTE_NOT_THERE,
            MarkerVote::VOTE_NOT_THERE,
            MarkerVote::VOTE_NOT_THERE,
            MarkerVote::VOTE_STILL_THERE,
        ];

        foreach ($voteTypes as $voteType) {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $this->postJson("/api/markers/{$marker->id}/vote", [
                'vote_type' => $voteType,
            ])->assertOk();
        }

        $marker->refresh();
        $this->assertTrue($marker->expires_at->lte(now()->addMinutes(1)));
    }

    public function test_not_there_ratio_does_not_force_early_expiry_below_quorum(): void
    {
        $marker = $this->createMarker();
        $marker->forceFill([
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
            'expires_at' => now()->addDays(2),
            'base_expires_at' => now()->addDays(2),
            'max_expires_at' => now()->addDays(7),
        ])->saveQuietly();

        foreach (range(1, 4) as $i) {
            $user = User::factory()->create();
            Sanctum::actingAs($user);

            $this->postJson("/api/markers/{$marker->id}/vote", [
                'vote_type' => MarkerVote::VOTE_NOT_THERE,
            ])->assertOk();
        }

        $marker->refresh();
        $this->assertTrue($marker->expires_at->gt(now()->addMinutes(1)));
    }

    private function createMarker(): Marker
    {
        $owner = User::factory()->create();

        return Marker::create([
            'user_id' => $owner->id,
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'category' => 'traffic',
            'description' => 'Test marker',
            'likes' => 0,
            'dislikes' => 0,
            'expires_at' => now()->addHours(4),
            'base_expires_at' => now()->addHours(4),
            'max_expires_at' => now()->addDays(7),
            'policy_snapshot' => [
                'base_lifetime_minutes' => 240,
                'min_lifetime_minutes' => 60,
                'max_lifetime_minutes' => 10080,
                'still_there_extension_minutes' => 30,
                'not_there_reduction_minutes' => 45,
                'grace_period_minutes' => 30,
                'early_expiry_quorum' => 5,
                'early_expiry_not_there_ratio' => 0.8,
                'early_expiry_minutes' => 0,
            ],
        ]);
    }
}
