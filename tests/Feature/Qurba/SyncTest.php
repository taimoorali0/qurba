<?php
// ===== QURBA: account sync merge rules =====
use App\Models\User;
use Illuminate\Support\Facades\DB;

function syncAs($test, User $u, array $extra = [])
{
    return $test->actingAs($u)->postJson('/api/v1/sync', array_replace_recursive([
        'device' => ['uuid' => 'dev-1', 'name' => 'test', 'platform' => 'web'],
        'consents' => ['items' => ['cloud_backup' => true], 't' => 1, 'version' => '1'],
        'bookmarks' => [], 'tasbeeh' => ['sessions' => [], 'custom' => [], 'removed' => [], 'daily' => []],
    ], $extra));
}

it('requires sign-in', function () {
    $this->postJson('/api/v1/sync', [])->assertUnauthorized();
});

it('keeps the newest bookmark change', function () {
    $u = User::factory()->create();
    syncAs($this, $u, ['bookmarks' => [['key' => '2:255', 'on' => true, 't' => 2000]]])->assertOk();
    syncAs($this, $u, ['bookmarks' => [['key' => '2:255', 'on' => false, 't' => 1000]]])->assertOk(); // older: ignored
    expect(DB::table('quran_bookmarks')->where('ayah_key', '2:255')->value('deleted_at'))->toBeNull();
    syncAs($this, $u, ['bookmarks' => [['key' => '2:255', 'on' => false, 't' => 3000]]])
        ->assertOk()->assertJsonPath('data.bookmarks.0.on', false);
});

it('combines tasbeeh sessions and keeps the highest daily total', function () {
    $u = User::factory()->create();
    $s = fn ($id) => ['id' => $id, 'typeId' => 'subhanallah', 'count' => 33, 'target' => 33, 'start' => 1, 'end' => 2];
    syncAs($this, $u, ['tasbeeh' => ['sessions' => [$s('a')], 'daily' => ['2026-10-01' => ['subhanallah' => 50]]]]);
    syncAs($this, $u, ['device' => ['uuid' => 'dev-2'], 'tasbeeh' => ['sessions' => [$s('a'), $s('b')], 'daily' => ['2026-10-01' => ['subhanallah' => 20]]]])
        ->assertOk()->assertJsonCount(2, 'data.tasbeeh.sessions');
    expect(DB::table('zikr_daily_totals')->value('total'))->toBe(50);
    expect(DB::table('user_devices')->where('user_id', $u->id)->count())->toBe(2);
});

it('records consent history only when a choice changes', function () {
    $u = User::factory()->create();
    syncAs($this, $u); syncAs($this, $u);
    expect(DB::table('user_consents')->where('consent_type', 'cloud_backup')->count())->toBe(1);
    syncAs($this, $u, ['consents' => ['items' => ['cloud_backup' => false]]]);
    expect(DB::table('user_consents')->where('consent_type', 'cloud_backup')->count())->toBe(2);
});
