<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Notification;
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

        $notification = $this->notificationService->create($notificationData);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'info',
            'message' => 'This is a test notification.',
            'read_at' => null,
        ]);
    }

    /** @test */
    public function it_retrieves_notifications_for_a_user()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        Notification::factory()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->count(2)->create(['user_id' => $anotherUser->id]);

        $notifications = $this->notificationService->getForUser($user->id);

        $this->assertCount(3, $notifications);
        $this->assertTrue($notifications->every(fn ($n) => $n->user_id === $user->id));
    }

    /** @test */
    public function it_marks_a_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

        $markedNotification = $this->notificationService->markAsRead($notification->id);

        $this->assertInstanceOf(Notification::class, $markedNotification);
        $this->assertNotNull($markedNotification->read_at);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => $markedNotification->read_at,
        ]);
    }

    /** @test */
    public function it_does_not_mark_non_existent_notification_as_read()
    {
        $markedNotification = $this->notificationService->markAsRead(999);

        $this->assertNull($markedNotification);
    }
}
