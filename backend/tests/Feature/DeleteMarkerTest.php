<?php

namespace Tests\Feature;

use App\Models\Marker;
use App\Models\MarkerImage;
use App\Models\MarkerVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteMarkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_marker(): void
    {
        $marker = $this->createMarker();

        $this->deleteJson("/api/markers/{$marker->id}")
            ->assertUnauthorized();
    }

    public function test_non_owner_non_admin_cannot_delete_marker(): void
    {
        $owner = User::factory()->create();
        $marker = $this->createMarker($owner);
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $this->deleteJson("/api/markers/{$marker->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('markers', ['id' => $marker->id]);
    }

    public function test_owner_can_delete_marker(): void
    {
        $owner = User::factory()->create();
        $marker = $this->createMarker($owner);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/markers/{$marker->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Marker deleted successfully.');

        $this->assertDatabaseMissing('markers', ['id' => $marker->id]);
    }

    public function test_admin_can_delete_any_marker(): void
    {
        $owner = User::factory()->create();
        $marker = $this->createMarker($owner);
        $admin = User::factory()->create(['is_admin' => true]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/markers/{$marker->id}")
            ->assertOk();

        $this->assertDatabaseMissing('markers', ['id' => $marker->id]);
    }

    public function test_deleting_marker_removes_related_images_and_votes(): void
    {
        $owner = User::factory()->create();
        $voter = User::factory()->create();
        $marker = $this->createMarker($owner);

        $image = MarkerImage::create([
            'marker_id' => $marker->id,
            'image_url' => 'markers/test-image.jpg',
        ]);

        $vote = MarkerVote::create([
            'marker_id' => $marker->id,
            'user_id' => $voter->id,
            'vote_type' => MarkerVote::VOTE_STILL_THERE,
        ]);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/markers/{$marker->id}")
            ->assertOk();

        $this->assertDatabaseMissing('markers', ['id' => $marker->id]);
        $this->assertDatabaseMissing('marker_images', ['id' => $image->id]);
        $this->assertDatabaseMissing('marker_votes', ['id' => $vote->id]);
    }

    private function createMarker(?User $owner = null): Marker
    {
        $owner ??= User::factory()->create();

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
