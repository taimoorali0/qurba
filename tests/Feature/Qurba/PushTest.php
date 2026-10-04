<?php
// ===== QURBA: reminder scheduling =====
use Illuminate\Support\Facades\DB;

it('stores only future reminders for a subscribed device', function () {
    $this->postJson('/api/v1/push/subscribe', ['device' => 'd1', 'subscription' => ['endpoint' => 'https://push.example.com/abc',
        'keys' => ['p256dh' => 'k', 'auth' => 'a']]])->assertOk();
    $this->postJson('/api/v1/push/schedule', ['device' => 'd1', 'items' => [
        ['kind' => 'fajr', 'fire_at' => now()->addHour()->toIso8601String(), 'title' => 'Fajr'],
        ['kind' => 'isha', 'fire_at' => now()->subHour()->toIso8601String(), 'title' => 'Isha'],
        ['kind' => 'dhuhr', 'fire_at' => now()->addDays(20)->toIso8601String(), 'title' => 'Too far'],
    ]])->assertOk();
    expect(DB::table('reminder_jobs')->pluck('kind')->all())->toBe(['fajr']);
});

it('refuses to schedule for an unknown device', function () {
    $this->postJson('/api/v1/push/schedule', ['device' => 'nope', 'items' => []])->assertNotFound();
});
