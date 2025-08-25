<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetView;
use App\Models\User;
use App\Services\ViewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ViewService $viewService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewService = new ViewService;
    }

    /** @test */
    public function it_records_a_new_asset_view()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        $assetView = $this->viewService->record($user, $asset);

        $this->assertInstanceOf(AssetView::class, $assetView);
        $this->assertDatabaseHas('asset_views', [
            'user_id' => $user->id,
            'asset_id' => $asset->id,
        ]);
    }

    /** @test */
    public function it_updates_an_existing_asset_view()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        // First record
        $firstView = $this->viewService->record($user, $asset);
        $this->travel(5)->minutes(); // Simulate time passing

        // Second record (should update the existing one)
        $secondView = $this->viewService->record($user, $asset);

        $this->assertEquals($firstView->id, $secondView->id);
        $this->assertGreaterThan($firstView->last_seen_at, $secondView->last_seen_at);
    }

    /** @test */
    public function it_retrieves_views_for_an_asset()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $asset = Asset::factory()->create();

        $this->viewService->record($user1, $asset);
        $this->viewService->record($user2, $asset);

        $views = $this->viewService->getForAsset($asset);

        $this->assertCount(2, $views);
        $this->assertTrue($views->contains(function ($view) use ($user1) {
            return $view->user_id === $user1->id;
        }));
        $this->assertTrue($views->contains(function ($view) use ($user2) {
            return $view->user_id === $user2->id;
        }));
    }

    /** @test */
    public function it_returns_empty_collection_when_no_views_for_asset()
    {
        $asset = Asset::factory()->create();

        $views = $this->viewService->getForAsset($asset);

        $this->assertEmpty($views);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $views);
    }

    /** @test */
    public function it_retrieves_views_by_user()
    {
        $user = User::factory()->create();
        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();

        $this->viewService->record($user, $asset1);
        $this->viewService->record($user, $asset2);

        $views = $this->viewService->getByUser($user);

        $this->assertCount(2, $views);
        $this->assertTrue($views->contains(function ($view) use ($asset1) {
            return $view->asset_id === $asset1->id;
        }));
        $this->assertTrue($views->contains(function ($view) use ($asset2) {
            return $view->asset_id === $asset2->id;
        }));
    }

    /** @test */
    public function it_returns_empty_collection_when_no_views_for_user()
    {
        $user = User::factory()->create();

        $views = $this->viewService->getByUser($user);

        $this->assertEmpty($views);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $views);
    }
}
