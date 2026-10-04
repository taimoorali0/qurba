<?php
// ===== QURBA: account sync (push local changes, receive merged state) =====
// Merge rules: bookmarks / progress / preferences = last write wins by client timestamp;
// tasbeeh sessions = union by uuid; daily totals = highest value per day + zikr.
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    private const BUILTIN = ['subhanallah', 'alhamdulillah', 'allahuakbar', 'lailahaillallah'];

    private static function ms(?string $ts): int { return $ts ? Carbon::parse($ts)->getTimestampMs() : 0; }
    private static function at(int|float|null $ms): Carbon { return $ms ? Carbon::createFromTimestampMs((int) $ms) : now(); }
    private static function typeKey(int $userId, string $clientId): string { return substr(hash('sha256', "{$userId}:{$clientId}"), 0, 36); }

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'device.uuid' => 'required|string|max:64',
            'bookmarks' => 'array|max:7000',
            'consents' => 'array',
            'tasbeeh.sessions' => 'array|max:500',
            'tasbeeh.custom' => 'array|max:200',
            'tasbeeh.removed' => 'array|max:200',
            'tasbeeh.daily' => 'array|max:400',
        ]);
        $u = $request->user();
        $now = now();

        DB::transaction(function () use ($request, $u, $now) {
            // ---- Consents (history kept: a new row only when a choice changes) ----
            foreach ((array) $request->input('consents.items', []) as $type => $granted) {
                $type = substr((string) $type, 0, 40);
                $last = DB::table('user_consents')->where('user_id', $u->id)->where('consent_type', $type)->orderByDesc('id')->value('granted');
                if ($last === null || (bool) $last !== (bool) $granted) {
                    DB::table('user_consents')->insert(['user_id' => $u->id, 'consent_type' => $type, 'granted' => (bool) $granted,
                        'policy_version' => substr((string) $request->input('consents.version', '1'), 0, 20),
                        'decided_at' => self::at($request->input('consents.t')), 'created_at' => $now, 'updated_at' => $now]);
                }
            }

            // ---- Device ----
            $dev = ['name' => substr((string) $request->input('device.name', ''), 0, 120), 'platform' => substr((string) $request->input('device.platform', 'web'), 0, 20),
                'app_version' => substr((string) $request->input('device.version', ''), 0, 20), 'last_synced_at' => $now, 'updated_at' => $now];
            $q = DB::table('user_devices')->where('user_id', $u->id)->where('device_uuid', $request->input('device.uuid'));
            $q->exists() ? $q->update($dev) : DB::table('user_devices')->insert($dev + ['user_id' => $u->id, 'device_uuid' => $request->input('device.uuid'), 'created_at' => $now]);

            // ---- Bookmarks (LWW, soft-deleted when removed) ----
            foreach ((array) $request->input('bookmarks', []) as $b) {
                $key = substr((string) ($b['key'] ?? ''), 0, 8);
                if (! preg_match('/^\d{1,3}:\d{1,3}$/', $key)) continue;
                $t = self::at($b['t'] ?? null);
                $row = DB::table('quran_bookmarks')->where('user_id', $u->id)->where('ayah_key', $key)->first();
                $vals = ['updated_at' => $t, 'deleted_at' => ! empty($b['on']) ? null : $t];
                if (! $row) {
                    DB::table('quran_bookmarks')->insert($vals + ['uuid' => (string) Str::uuid(), 'user_id' => $u->id, 'ayah_key' => $key, 'created_at' => $t]);
                } elseif ($t->getTimestampMs() > self::ms($row->updated_at)) {
                    DB::table('quran_bookmarks')->where('id', $row->id)->update($vals);
                }
            }

            // ---- Last read ----
            if ($lr = $request->input('lastRead')) {
                $t = self::at($lr['at'] ?? null);
                $row = DB::table('quran_progress')->where('user_id', $u->id)->first();
                $key = substr((string) ($lr['key'] ?? ''), 0, 8);
                if (preg_match('/^\d{1,3}:\d{1,3}$/', $key) && (! $row || $t->getTimestampMs() > self::ms($row->updated_at))) {
                    DB::table('quran_progress')->updateOrInsert(['user_id' => $u->id], ['last_ayah_key' => $key, 'updated_at' => $t, 'created_at' => $row->created_at ?? $t]);
                }
            }

            // ---- Preferences + prayer (LWW on one client timestamp) ----
            if ($p = $request->input('prefs')) {
                $t = self::at($p['t'] ?? null);
                $row = DB::table('user_preferences')->where('user_id', $u->id)->first();
                if (! $row || $t->getTimestampMs() > self::ms($row->client_updated_at)) {
                    DB::table('user_preferences')->updateOrInsert(['user_id' => $u->id], [
                        'language' => substr((string) ($p['language'] ?? 'en'), 0, 10),
                        'country' => isset($p['place']['country']) && $p['place']['country'] ? substr($p['place']['country'], 0, 2) : null,
                        'quran' => json_encode($p['reader'] ?? null), 'audio' => json_encode($p['audio'] ?? null), 'tasbeeh' => json_encode($p['tasbeeh'] ?? null),
                        'client_updated_at' => $t, 'updated_at' => $now, 'created_at' => $row->created_at ?? $now,
                    ]);
                    $pl = (array) ($p['place'] ?? []); $pr = (array) ($p['prayer'] ?? []);
                    $pp = DB::table('prayer_preferences')->where('user_id', $u->id)->first();
                    DB::table('prayer_preferences')->updateOrInsert(['user_id' => $u->id], [
                        'latitude' => $pl['lat'] ?? null, 'longitude' => $pl['lng'] ?? null, 'city' => isset($pl['label']) ? substr($pl['label'], 0, 120) : null,
                        'timezone' => isset($pl['tz']) ? substr($pl['tz'], 0, 64) : null, 'country' => $pl['country'] ?? null ?: null,
                        'location_mode' => ($pl['mode'] ?? 'manual') === 'device' ? 'device' : 'manual',
                        'calculation_method' => ($pr['method'] ?? '') ?: null, 'asr_method' => ($pr['asr'] ?? '') ?: null,
                        'adjustments_minutes' => json_encode($pr['adjust'] ?? null), 'updated_at' => $now, 'created_at' => $pp->created_at ?? $now,
                    ]);
                }
            }

            // ---- Tasbeeh: custom zikr types ----
            $typeId = function (string $clientId, ?array $custom = null) use ($u, $now) {
                $key = self::typeKey($u->id, $clientId);
                $row = DB::table('zikr_types')->where('uuid', $key)->first();
                if ($row) return $row->id;
                return DB::table('zikr_types')->insertGetId(['uuid' => $key, 'user_id' => $u->id,
                    'text_arabic' => $custom['ar'] ?? null, 'label' => json_encode(['_id' => $clientId, 'text' => $custom['ar'] ?? $clientId], JSON_UNESCAPED_UNICODE),
                    'default_target' => (int) ($custom['target'] ?? 33), 'created_at' => $now, 'updated_at' => $now]);
            };
            foreach ((array) $request->input('tasbeeh.custom', []) as $c) {
                if (empty($c['id']) || in_array($c['id'], self::BUILTIN, true)) continue;
                $id = $typeId((string) $c['id'], $c);
                DB::table('zikr_types')->where('id', $id)->whereNull('deleted_at')->update(['text_arabic' => substr((string) ($c['ar'] ?? ''), 0, 250), 'default_target' => max(1, (int) ($c['target'] ?? 33)), 'updated_at' => $now]);
            }
            foreach ((array) $request->input('tasbeeh.removed', []) as $rid) {
                DB::table('zikr_types')->where('uuid', self::typeKey($u->id, (string) $rid))->update(['deleted_at' => $now]);
            }

            // ---- Tasbeeh: sessions (union) ----
            $known = DB::table('zikr_sessions')->where('user_id', $u->id)->pluck('uuid')->flip();
            foreach ((array) $request->input('tasbeeh.sessions', []) as $s) {
                $sid = substr((string) ($s['id'] ?? ''), 0, 36);
                if ($sid === '' || isset($known[$sid]) || empty($s['typeId'])) continue;
                DB::table('zikr_sessions')->insert(['uuid' => $sid, 'user_id' => $u->id, 'zikr_type_id' => $typeId((string) $s['typeId']),
                    'count' => max(0, (int) ($s['count'] ?? 0)), 'target' => (int) ($s['target'] ?? 0) ?: null,
                    'started_at' => self::at($s['start'] ?? null), 'ended_at' => self::at($s['end'] ?? null), 'created_at' => $now, 'updated_at' => $now]);
            }

            // ---- Tasbeeh: daily totals (keep the highest) ----
            foreach ((array) $request->input('tasbeeh.daily', []) as $day => $types) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $day)) continue;
                foreach ((array) $types as $tid => $n) {
                    $zt = $typeId((string) $tid);
                    $row = DB::table('zikr_daily_totals')->where(['user_id' => $u->id, 'zikr_type_id' => $zt, 'day' => $day])->first();
                    if (! $row) DB::table('zikr_daily_totals')->insert(['user_id' => $u->id, 'zikr_type_id' => $zt, 'day' => $day, 'total' => max(0, (int) $n), 'created_at' => $now, 'updated_at' => $now]);
                    elseif ((int) $n > $row->total) DB::table('zikr_daily_totals')->where('id', $row->id)->update(['total' => (int) $n, 'updated_at' => $now]);
                }
            }

            DB::table('sync_logs')->insert(['user_id' => $u->id, 'status' => 'success', 'synced_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                'pushed' => count((array) $request->input('bookmarks', [])) + count((array) $request->input('tasbeeh.sessions', []))]);
        });

        return response()->json(['data' => $this->state($u->id)]);
    }

    /** Full merged state for this user (small: a few KB). */
    private function state(int $uid): array
    {
        $types = DB::table('zikr_types')->where('user_id', $uid)->get()->keyBy('id');
        $clientId = fn ($id) => json_decode($types[$id]->label ?? '{}', true)['_id'] ?? null;
        $prefs = DB::table('user_preferences')->where('user_id', $uid)->first();
        $prayer = DB::table('prayer_preferences')->where('user_id', $uid)->first();
        $progress = DB::table('quran_progress')->where('user_id', $uid)->first();

        return [
            'bookmarks' => DB::table('quran_bookmarks')->where('user_id', $uid)->get(['ayah_key', 'deleted_at', 'updated_at'])
                ->map(fn ($b) => ['key' => $b->ayah_key, 'on' => $b->deleted_at === null, 't' => self::ms($b->updated_at)]),
            'lastRead' => $progress ? (function () use ($progress) {
                [$s, $a] = array_map('intval', explode(':', $progress->last_ayah_key));
                return ['key' => $progress->last_ayah_key, 'surah' => $s, 'ayah' => $a,
                    'name' => DB::table('quran_surahs')->where('id', $s)->value('name_simple'), 'at' => self::ms($progress->updated_at)];
            })() : null,
            'prefs' => $prefs ? [
                't' => self::ms($prefs->client_updated_at), 'language' => $prefs->language,
                'reader' => json_decode($prefs->quran ?? 'null', true), 'audio' => json_decode($prefs->audio ?? 'null', true), 'tasbeeh' => json_decode($prefs->tasbeeh ?? 'null', true),
                'place' => $prayer && $prayer->latitude !== null ? ['lat' => (float) $prayer->latitude, 'lng' => (float) $prayer->longitude, 'label' => $prayer->city,
                    'tz' => $prayer->timezone, 'country' => $prayer->country ?? '', 'mode' => $prayer->location_mode] : null,
                'prayer' => $prayer ? ['method' => $prayer->calculation_method ?? '', 'asr' => $prayer->asr_method ?? '', 'adjust' => json_decode($prayer->adjustments_minutes ?? 'null', true)] : null,
            ] : null,
            'tasbeeh' => [
                'custom' => $types->filter(fn ($z) => $z->deleted_at === null && $z->text_arabic !== null && ! in_array($clientId($z->id), self::BUILTIN, true))
                    ->map(fn ($z) => ['id' => $clientId($z->id), 'ar' => $z->text_arabic, 'target' => $z->default_target])->values(),
                'removed' => $types->filter(fn ($z) => $z->deleted_at !== null)->map(fn ($z) => $clientId($z->id))->values(),
                'sessions' => DB::table('zikr_sessions')->where('user_id', $uid)->whereNull('deleted_at')->orderByDesc('ended_at')->limit(200)->get()
                    ->map(fn ($s) => ['id' => $s->uuid, 'typeId' => $clientId($s->zikr_type_id), 'count' => $s->count, 'target' => $s->target ?? 0,
                        'start' => self::ms($s->started_at), 'end' => self::ms($s->ended_at)]),
                'daily' => DB::table('zikr_daily_totals')->where('user_id', $uid)->where('day', '>=', now()->subDays(60)->toDateString())->get()
                    ->groupBy(fn ($d) => Carbon::parse($d->day)->toDateString())
                    ->map(fn ($rows) => $rows->mapWithKeys(fn ($d) => [$clientId($d->zikr_type_id) => $d->total])),
            ],
            'consents' => DB::table('user_consents')->where('user_id', $uid)->orderBy('id')->get(['consent_type', 'granted'])
                ->mapWithKeys(fn ($c) => [$c->consent_type => (bool) $c->granted]),
            'devices' => DB::table('user_devices')->where('user_id', $uid)->orderByDesc('last_synced_at')->get(['id', 'device_uuid', 'name', 'platform', 'last_synced_at']),
        ];
    }

    public function export(Request $request)
    {
        $uid = $request->user()->id;
        $tables = ['user_preferences', 'user_consents', 'user_devices', 'quran_bookmarks', 'quran_progress', 'quran_listening_progress',
            'downloads', 'zikr_types', 'zikr_sessions', 'zikr_daily_totals', 'user_adhkar_progress', 'prayer_preferences', 'reminders', 'sync_logs'];
        $out = ['exported_at' => now()->toIso8601String(), 'user' => $request->user()->only(['name', 'email', 'created_at'])];
        foreach ($tables as $t) {
            $out[$t] = DB::table($t)->where('user_id', $uid)->get()->map(fn ($r) => collect((array) $r)->except(['user_id', 'push_subscription']));
        }
        return response()->json($out, 200, ['Content-Disposition' => 'attachment; filename="qurba-my-data.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function removeDevice(Request $request, int $id): JsonResponse
    {
        DB::table('user_devices')->where('user_id', $request->user()->id)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
