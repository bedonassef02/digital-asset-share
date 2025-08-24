<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Asset;
use App\Models\Share;
use App\Services\ShareService;
use Illuminate\Validation\ValidationException;

class ShareServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShareService $shareService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shareService = new ShareService();
    }

    /** @test */
    public function it_creates_a_share_link_without_password_or_expiry()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $share = $this->shareService->create($asset, [], $user->id);

        $this->assertInstanceOf(Share::class, $share);
        $this->assertNotNull($share->token);
        $this->assertNull($share->expires_at);
        $this->assertNull($share->password);
        $this->assertEquals($asset->id, $share->asset_id);
        $this->assertEquals($user->id, $share->user_id);
    }

    /** @test */
    public function it_creates_a_share_link_with_expiry()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $expiresAt = now()->addDays(7);

        $share = $this->shareService->create($asset, ['expires_at' => $expiresAt->toDateTimeString()], $user->id);

        $this->assertInstanceOf(Share::class, $share);
        $this->assertNotNull($share->token);
        $this->assertEquals($expiresAt->toDateTimeString(), $share->expires_at->toDateTimeString());
        $this->assertNull($share->password);
    }

    /** @test */
    public function it_creates_a_share_link_with_password()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $password = 'secret';

        $share = $this->shareService->create($asset, ['password' => $password], $user->id);

        $this->assertInstanceOf(Share::class, $share);
        $this->assertNotNull($share->token);
        $this->assertNotNull($share->password);
        $this->assertTrue(password_verify($password, $share->password));
        $this->assertNull($share->expires_at);
    }

    /** @test */
    public function it_resolves_a_share_link_without_password()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $share = Share::factory()->create(['asset_id' => $asset->id, 'user_id' => $user->id]);

        $resolvedAsset = $this->shareService->resolve($share->token);

        $this->assertInstanceOf(Asset::class, $resolvedAsset);
        $this->assertEquals($asset->id, $resolvedAsset->id);
    }

    /** @test */
    public function it_resolves_a_share_link_with_correct_password()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $password = 'secret';
        $share = Share::factory()->create(['asset_id' => $asset->id, 'user_id' => $user->id, 'password' => bcrypt($password)]);

        $resolvedAsset = $this->shareService->resolve($share->token, $password);

        $this->assertInstanceOf(Asset::class, $resolvedAsset);
        $this->assertEquals($asset->id, $resolvedAsset->id);
    }

    /** @test */
    public function it_fails_to_resolve_share_link_with_incorrect_password()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $password = 'secret';
        $share = Share::factory()->create(['asset_id' => $asset->id, 'user_id' => $user->id, 'password' => bcrypt($password)]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Incorrect password.');

        $this->shareService->resolve($share->token, 'wrong-password');
    }

    /** @test */
    public function it_fails_to_resolve_expired_share_link()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $share = Share::factory()->create(['asset_id' => $asset->id, 'user_id' => $user->id, 'expires_at' => now()->subDay()]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Share link has expired.');

        $this->shareService->resolve($share->token);
    }

    /** @test */
    public function it_fails_to_resolve_non_existent_share_link()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [App\Models\Share].');

        $this->shareService->resolve('nonexistent_token');
    }

    /** @test */
    public function it_lists_share_links_for_authenticated_user()
    {
        $user = User::factory()->create();
        $asset1 = Asset::factory()->create(['user_id' => $user->id]);
        $asset2 = Asset::factory()->create(['user_id' => $user->id]);
        $share1 = Share::factory()->create(['asset_id' => $asset1->id, 'user_id' => $user->id]);
        $share2 = Share::factory()->create(['asset_id' => $asset2->id, 'user_id' => $user->id]);
        Share::factory()->create(); // Share for another user

        $shares = $this->shareService->list($user->id);

        $this->assertCount(2, $shares);
        $this->assertTrue($shares->contains(fn ($s) => $s->id === $share1->id));
        $this->assertTrue($shares->contains(fn ($s) => $s->id === $share2->id));
    }

    /** @test */
    public function it_revokes_a_share_link()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $share = Share::factory()->create(['asset_id' => $asset->id, 'user_id' => $user->id]);

        $this->shareService->revoke($share->token, $user->id);

        $this->assertDatabaseMissing('shares', ['id' => $share->id]);
    }

    /** @test */
    public function it_fails_to_revoke_a_share_link_if_unauthorized()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $share = Share::factory()->create(['asset_id' => $asset->id, 'user_id' => $user->id]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('You are not authorized to revoke this share link.');

        $this->shareService->revoke($share->token, $anotherUser->id);
    }
}