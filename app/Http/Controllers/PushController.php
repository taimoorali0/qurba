<?php
// ===== QURBA: push subscribe + reminder schedule (times are computed on the device) =====
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PushController extends Controller
{
    public function key(): JsonResponse
    {
        return response()->json(['key' => config('qurba.vapid.public')]);
    }

    public function subscribe(Request $r): JsonResponse
    {
        $d = $r->validate([
            'device' => 'required|string|max:64',
            'subscription.endpoint' => 'required|url|max:1000',
            'subscription.keys.p256dh' => 'required|string|max:255',
            'subscription.keys.auth' => 'required|string|max:255',
            'locale' => 'nullable|string|max:10',
        ]);
        $now = now();
        DB::table('push_devices')->updateOrInsert(['device_uuid' => $d['device']], [
            'user_id' => $r->user()?->id, 'endpoint' => $d['subscription']['endpoint'],
            'p256dh' => $d['subscription']['keys']['p256dh'], 'auth' => $d['subscription']['keys']['auth'],
            'locale' => $d['locale'] ?? 'en', 'last_seen_at' => $now, 'updated_at' => $now, 'created_at' => $now,
        ]);
        return response()->json(['ok' => true]);
    }

    /** Replaces all future, unsent reminders for this device. */
    public function schedule(Request $r): JsonResponse
    {
        $d = $r->validate([
            'device' => 'required|string|max:64',
            'items' => 'array|max:120',
            'items.*.kind' => 'required|string|max:30',
            'items.*.fire_at' => 'required|date',
            'items.*.title' => 'required|string|max:120',
            'items.*.body' => 'nullable|string|max:240',
            'items.*.url' => 'nullable|string|max:120',
        ]);
        $dev = DB::table('push_devices')->where('device_uuid', $d['device'])->first();
        if (! $dev) return response()->json(['ok' => false, 'error' => 'not_subscribed'], 404);

        $now = now();
        DB::transaction(function () use ($d, $dev, $now) {
            DB::table('reminder_jobs')->where('push_device_id', $dev->id)->whereNull('sent_at')->where('fire_at', '>', $now)->delete();
            $rows = collect($d['items'] ?? [])->map(fn ($i) => [
                'push_device_id' => $dev->id, 'kind' => $i['kind'], 'fire_at' => Carbon::parse($i['fire_at'])->utc(),
                'title' => $i['title'], 'body' => $i['body'] ?? null,
                'url' => str_starts_with($i['url'] ?? '/', '/') ? ($i['url'] ?? '/') : '/',
            ])->filter(fn ($i) => $i['fire_at']->isFuture() && $i['fire_at']->lt($now->copy()->addDays(8)))
              ->unique(fn ($i) => $i['kind'] . $i['fire_at'])->values()->all();
            foreach (array_chunk($rows, 100) as $chunk) DB::table('reminder_jobs')->insertOrIgnore($chunk);
            DB::table('push_devices')->where('id', $dev->id)->update(['last_seen_at' => $now]);
        });
        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $r): JsonResponse
    {
        $r->validate(['device' => 'required|string|max:64']);
        DB::table('push_devices')->where('device_uuid', $r->input('device'))->delete();
        return response()->json(['ok' => true]);
    }
}
