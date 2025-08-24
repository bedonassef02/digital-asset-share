<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Collection;
use App\Models\Asset;
use App\Services\CollectionService;

class CollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CollectionService $collectionService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collectionService = new CollectionService();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_creates_a_new_collection()
    {
        $data = [
            'name' => 'My First Collection',
            'description' => 'A collection of my favorite assets.',
            'user_id' => $this->user->id,
        ];

        $collection = $this->collectionService->create($data);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals('My First Collection', $collection->name);
        $this->assertDatabaseHas('collections', $data);
    }

    /** @test */
    public function it_creates_a_nested_collection()
    {
        $parentCollection = Collection::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'name' => 'Nested Collection',
            'description' => 'A sub-collection.',
            'user_id' => $this->user->id,
            'parent_id' => $parentCollection->id,
        ];

        $collection = $this->collectionService->create($data);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals('Nested Collection', $collection->name);
        $this->assertEquals($parentCollection->id, $collection->parent_id);
        $this->assertDatabaseHas('collections', $data);
    }

    /** @test */
    public function it_updates_an_existing_collection()
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        $updatedData = [
            'name' => 'Updated Collection Name',
            'description' => 'Updated description.',
        ];

        $updatedCollection = $this->collectionService->update($collection->id, $updatedData, $this->user->id);

        $this->assertInstanceOf(Collection::class, $updatedCollection);
        $this->assertEquals('Updated Collection Name', $updatedCollection->name);
        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'name' => 'Updated Collection Name',
            'description' => 'Updated description.',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_existent_collection()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->collectionService->update(999, ['name' => 'Non Existent'], $this->user->id);
    }

    /** @test */
    public function it_deletes_a_collection()
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        $result = $this->collectionService->delete($collection->id, $this->user->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    }

    /** @test */
    public function it_throws_exception_when_deleting_non_existent_collection()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->collectionService->delete(999, $this->user->id);
    }

    /** @test */
    public function it_finds_a_collection_by_id()
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        $foundCollection = $this->collectionService->find($collection->id, $this->user->id);

        $this->assertInstanceOf(Collection::class, $foundCollection);
        $this->assertEquals($collection->id, $foundCollection->id);
    }

    /** @test */
    public function it_throws_exception_when_finding_non_existent_collection()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->collectionService->find(999, $this->user->id);
    }

    /** @test */
    public function it_retrieves_all_collections_for_a_user()
    {
        Collection::factory()->count(3)->create(['user_id' => $this->user->id]);
        Collection::factory()->count(2)->create(); // Other user's collections

        $collections = $this->collectionService->findAll($this->user->id);

        $this->assertCount(3, $collections->items());
        $this->assertTrue($collections->every(fn ($c) => $c->user_id === $this->user->id));
    }

    /** @test */
    public function it_adds_assets_to_a_collection()
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $assets = Asset::factory()->count(2)->create(['user_id' => $this->user->id]);

        $this->collectionService->addAssets($collection->id, $assets->pluck('id')->toArray(), $this->user->id);

        $this->assertCount(2, $collection->assets);
        $this->assertTrue($collection->assets->contains($assets->first()));
        $this->assertTrue($collection->assets->contains($assets->last()));
    }

    /** @test */
    public function it_removes_assets_from_a_collection()
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $assets = Asset::factory()->count(3)->create(['user_id' => $this->user->id]);
        $collection->assets()->attach($assets->pluck('id'));

        $this->assertCount(3, $collection->assets);

        $this->collectionService->removeAssets($collection->id, [$assets->first()->id], $this->user->id);

        $collection->refresh();
        $this->assertCount(2, $collection->assets);
        $this->assertFalse($collection->assets->contains($assets->first()));
    }

    /** @test */
    public function it_retrieves_assets_within_a_collection()
    {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $assets = Asset::factory()->count(3)->create(['user_id' => $this->user->id]);
        $collection->assets()->attach($assets->pluck('id'));

        $retrievedAssets = $this->collectionService->getCollectionAssets($collection->id, 15, $this->user->id);

        $this->assertCount(3, $retrievedAssets->items());
        $this->assertTrue($retrievedAssets->pluck('id')->contains($assets->first()->id));
    }

    /** @test */
    public function it_retrieves_root_collections_for_a_user()
    {
        // Create 2 root collections for the user
        Collection::factory()->count(2)->create(['user_id' => $this->user->id, 'parent_id' => null]);

        // Create a child collection whose parent is one of the existing root collections
        $existingRootCollection = Collection::where('user_id', $this->user->id)->whereNull('parent_id')->first();
        Collection::factory()->create(['user_id' => $this->user->id, 'parent_id' => $existingRootCollection->id]);

        // Create a root collection for another user
        Collection::factory()->create();

        $rootCollections = $this->collectionService->getRootCollections($this->user->id);

        $this->assertCount(2, $rootCollections);
        $this->assertTrue($rootCollections->every(fn ($c) => $c->parent_id === null));
    }

    /** @test */
    public function it_retrieves_child_collections_for_a_parent()
    {
        $parent = Collection::factory()->create(['user_id' => $this->user->id]);
        Collection::factory()->count(2)->create(['user_id' => $this->user->id, 'parent_id' => $parent->id]);
        Collection::factory()->create(['user_id' => $this->user->id, 'parent_id' => null]); // Another root collection

        $childCollections = $this->collectionService->getChildCollections($parent->id, $this->user->id);

        $this->assertCount(2, $childCollections);
        $this->assertTrue($childCollections->every(fn ($c) => $c->parent_id === $parent->id));
    }
}
