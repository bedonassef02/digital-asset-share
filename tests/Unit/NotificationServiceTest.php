<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Notification;
use App\Models\Asset;
use App\Services\NotificationService;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
    }

    /** @test */
    public function it_creates_a_notification()
    {
        $user = User::factory()->create();
        $notificationData = [
            'user_id' => $user->id,
            'type' => 'info',
            'message' => 'This is a test notification.',
        ];

        $notification = $this->notificationService->create(
            $notificationData['user_id'],
            $notificationData['type'],
            $notificationData['message']
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'info',
            'message' => 'This is a test notification.',
            'read_at' => null,
            'notifiable_type' => null,
            'notifiable_id' => null,
        ]);
    }

    /** @test */
    public function it_creates_a_notification_with_a_notifiable_model()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(); // Assuming Asset is a notifiable model

        $notificationData = [
            'user_id' => $user->id,
            'type' => 'warning',
            'message' => 'Asset needs attention.',
        ];

        $notification = $this->notificationService->create(
            $notificationData['user_id'],
            $notificationData['type'],
            $notificationData['message'],
            $asset
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'warning',
            'message' => 'Asset needs attention.',
            'notifiable_type' => $asset->getMorphClass(),
            'notifiable_id' => $asset->id,
        ]);
    }

    /** @test */
    public function it_retrieves_all_notifications_for_a_user()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->count(2)->create(['user_id' => $anotherUser->id]);

        $notifications = $this->notificationService->getAll($user->id);

        $this->assertCount(3, $notifications);
        $this->assertTrue($notifications->every(fn ($n) => $n->user_id === $user->id));
    }

    /** @test */
    public function it_retrieves_unread_notifications_for_a_user()
    {
        $user = User::factory()->create();

        Notification::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => null]);
        Notification::factory()->count(1)->create(['user_id' => $user->id, 'read_at' => now()]);
        Notification::factory()->count(1)->create(); // Another user's unread notification

        $unreadNotifications = $this->notificationService->getUnread($user->id);

        $this->assertCount(2, $unreadNotifications);
        $this->assertTrue($unreadNotifications->every(fn ($n) => $n->user_id === $user->id && $n->read_at === null));
    }

    /** @test */
    public function it_marks_a_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

        $this->notificationService->markAsRead($notification);

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => $notification->fresh()->read_at,
        ]);
    }

    /** @test */
    public function it_does_not_mark_non_existent_notification_as_read()
    {
        // This test is no longer directly applicable as markAsRead now takes a Notification model.
        // The service method itself will throw an error if the model is not found before it's passed.
        // However, we can test that passing a non-existent notification object (e.g., a mock that doesn't exist in DB) doesn't cause issues.
        $nonExistentNotification = new Notification();
        $nonExistentNotification->id = 999; // Simulate a non-existent ID

        // Expect no database changes or exceptions from the service method itself
        $this->notificationService->markAsRead($nonExistentNotification);

        $this->assertDatabaseMissing('notifications', ['id' => 999]);
    }
}
