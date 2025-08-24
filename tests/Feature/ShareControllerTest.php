<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Asset;
use App\Models\Share;
use App\Models\AssetView;

class ShareControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the database with necessary data if needed
    }

    /** @test */
    public function unauthenticated_user_cannot_resolve_shared_link()
    {
        $asset = Asset::factory()->create();
        $share = Share::factory()->create(['asset_id' => $asset->id]);

        $response = $this->getJson("/api/shares/{$share->token}");

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_resolve_shared_link_and_view_is_recorded()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();
        $share = Share::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/shares/{$share->token}");

        $response->assertStatus(200);
        $response->assertJson(['id' => $asset->id]);

        $this->assertDatabaseHas('asset_views', [
            'user_id' => $user->id,
            'asset_id' => $asset->id,
        ]);
    }

    /** @test */
    public function resolving_shared_link_updates_last_seen_at()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();
        $share = Share::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs($user, 'sanctum');

        // First view
        $this->getJson("/api/shares/{$share->token}");

        // Wait a bit to ensure last_seen_at changes
        sleep(2);

        $oldLastSeenAt = AssetView::where('user_id', $user->id)
                                  ->where('asset_id', $asset->id)
                                  ->first()
                                  ->last_seen_at;

        // Second view
        $response = $this->getJson("/api/shares/{$share->token}");

        $response->assertStatus(200);

        $newLastSeenAt = AssetView::where('user_id', $user->id)
                                  ->where('asset_id', $asset->id)
                                  ->first()
                                  ->last_seen_at;

        $this->assertGreaterThan($oldLastSeenAt, $newLastSeenAt);
    }

    // Add more tests for create, list, revoke methods if needed
}
