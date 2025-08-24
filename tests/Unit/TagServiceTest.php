<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Asset;
use App\Models\Tag;
use App\Services\TagService;

class TagServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TagService $tagService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagService = new TagService();
    }

    /** @test */
    public function it_replaces_all_tags_for_an_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $existingTag = Tag::factory()->create(['name' => 'old_tag']);
        $asset->tags()->attach($existingTag);

        $newTags = ['tag1', 'tag2'];
        $this->tagService->replaceAll($asset->id, $newTags);

        $this->assertCount(2, $asset->fresh()->tags);
        $this->assertTrue($asset->fresh()->tags->contains('name', 'tag1'));
        $this->assertTrue($asset->fresh()->tags->contains('name', 'tag2'));
        $this->assertFalse($asset->fresh()->tags->contains('name', 'old_tag'));
    }

    /** @test */
    public function it_adds_a_new_tag_to_an_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $this->tagService->add($asset->id, 'new_tag');

        $this->assertCount(1, $asset->fresh()->tags);
        $this->assertTrue($asset->fresh()->tags->contains('name', 'new_tag'));
    }

    /** @test */
    public function it_does_not_add_duplicate_tags_to_an_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $existingTag = Tag::factory()->create(['name' => 'existing_tag']);
        $asset->tags()->attach($existingTag);

        $this->tagService->add($asset->id, 'existing_tag');

        $this->assertCount(1, $asset->fresh()->tags);
    }

    /** @test */
    public function it_removes_an_existing_tag_from_an_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $tagToRemove = Tag::factory()->create(['name' => 'remove_me']);
        $asset->tags()->attach($tagToRemove);
        $asset->tags()->attach(Tag::factory()->create(['name' => 'keep_me']));

        $this->tagService->remove($asset->id, 'remove_me');

        $this->assertCount(1, $asset->fresh()->tags);
        $this->assertFalse($asset->fresh()->tags->contains('name', 'remove_me'));
        $this->assertTrue($asset->fresh()->tags->contains('name', 'keep_me'));
    }

    /** @test */
    public function it_does_nothing_when_removing_a_non_existent_tag()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $existingTag = Tag::factory()->create(['name' => 'existing_tag']);
        $asset->tags()->attach($existingTag);

        $this->tagService->remove($asset->id, 'non_existent_tag');

        $this->assertCount(1, $asset->fresh()->tags);
        $this->assertTrue($asset->fresh()->tags->contains('name', 'existing_tag'));
    }
}
