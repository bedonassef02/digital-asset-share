<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Asset;
use App\Models\AssetVersion;
use App\Models\Tag;
use App\Services\SearchService;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = new SearchService();
    }

    /** @test */
    public function it_searches_assets_by_name_and_description_in_asset_versions()
    {
        $user = User::factory()->create();
        $asset1 = Asset::factory()->create(['user_id' => $user->id]);
        $asset2 = Asset::factory()->create(['user_id' => $user->id]);
        $asset3 = Asset::factory()->create(['user_id' => $user->id]);

        AssetVersion::factory()->create([
            'asset_id' => $asset1->id,
            'name' => 'Document A',
            'description' => 'This is a test document.',
        ]);
        AssetVersion::factory()->create([
            'asset_id' => $asset2->id,
            'name' => 'Image B',
            'description' => 'Another test image.',
        ]);
        AssetVersion::factory()->create([
            'asset_id' => $asset3->id,
            'name' => 'Report C',
            'description' => 'Final report for project.',
        ]);

        $results = $this->searchService->search('document', $user->id);

        $this->assertCount(1, $results);
        $this->assertEquals('Document A', $results->first()->name);
    }

    /** @test */
    public function it_searches_assets_by_tags()
    {
        $user = User::factory()->create();
        $asset1 = Asset::factory()->create(['user_id' => $user->id]);
        $asset2 = Asset::factory()->create(['user_id' => $user->id]);

        $tag1 = Tag::factory()->create(['name' => 'important']);
        $tag2 = Tag::factory()->create(['name' => 'draft']);

        $asset1->tags()->attach($tag1);
        $asset2->tags()->attach($tag2);

        AssetVersion::factory()->create([
            'asset_id' => $asset1->id,
            'name' => 'Doc 1',
        ]);
        AssetVersion::factory()->create([
            'asset_id' => $asset2->id,
            'name' => 'Doc 2',
        ]);

        $results = $this->searchService->search('important', $user->id);

        $this->assertCount(1, $results);
        $this->assertEquals('Doc 1', $results->first()->name);
    }

    /** @test */
    public function it_only_returns_assets_belonging_to_the_authenticated_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $asset1 = Asset::factory()->create(['user_id' => $user1->id]);
        $asset2 = Asset::factory()->create(['user_id' => $user2->id]);

        AssetVersion::factory()->create([
            'asset_id' => $asset1->id,
            'name' => 'User1 Doc',
        ]);
        AssetVersion::factory()->create([
            'asset_id' => $asset2->id,
            'name' => 'User2 Doc',
        ]);

        $results = $this->searchService->search('Doc', $user1->id);

        $this->assertCount(1, $results);
        $this->assertEquals('User1 Doc', $results->first()->name);
    }
}
