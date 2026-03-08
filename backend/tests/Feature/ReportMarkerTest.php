<?php

namespace Tests\Feature;

use App\Models\Marker;
use App\Models\MarkerReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportMarkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_report_marker(): void
    {
        $marker = $this->createMarker();

        $this->postJson("/api/markers/{$marker->id}/report", [
            'reason' => 'spam',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_report_marker(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/report", [
            'reason' => 'inaccurate',
            'details' => 'Location description does not match the map point.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Marker reported successfully.')
            ->assertJsonPath('data.marker_id', $marker->id)
            ->assertJsonPath('data.reason', 'inaccurate');

        $this->assertDatabaseHas('marker_reports', [
            'marker_id' => $marker->id,
            'user_id' => $user->id,
            'reason' => 'inaccurate',
        ]);
    }

    public function test_duplicate_report_by_same_user_is_rejected(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        MarkerReport::create([
            'marker_id' => $marker->id,
            'user_id' => $user->id,
            'reason' => 'spam',
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/report", [
            'reason' => 'offensive',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'You have already reported this marker.');

        $this->assertDatabaseCount('marker_reports', 1);
    }

    public function test_reason_is_required_and_must_be_valid(): void
    {
        $user = User::factory()->create();
        $marker = $this->createMarker();

        Sanctum::actingAs($user);

        $this->postJson("/api/markers/{$marker->id}/report", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->postJson("/api/markers/{$marker->id}/report", [
            'reason' => 'not-valid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
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
            'expires_at' => now()->addWeek(),
        ]);
    }
}
