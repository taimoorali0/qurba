<x-filament-widgets::widget>
    <div class="q-hero">
        <div aria-hidden="true" class="q-hero-mark">قربة</div>
        <p class="q-hero-ar">ٱلسَّلَامُ عَلَيْكُمْ</p>
        <p class="q-hero-kicker">Assalamu Alaikum · Good {{ $part }}</p>
        <h2 class="q-hero-name">{{ $name }}</h2>
        <div class="q-orn"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.6 6.4L21 11l-6.4 2.6L12 20l-2.6-6.4L3 11l6.4-2.6z"/></svg></div>
        <p class="q-hero-meta">@if ($role){{ $role }} · @endif{{ $date }}@if ($hijri) · {{ $hijri }}@endif</p>
        <div class="q-actions">
            @foreach ([
                ['Review content', url('/admin/content-sources'), 'heroicon-o-shield-check'],
                ['Adhkar & duas', url('/admin/adhkars'), 'heroicon-o-sun'],
                ['Audio library', url('/admin/audio-files'), 'heroicon-o-musical-note'],
                ['Learn requests', url('/admin/learn-interests'), 'heroicon-o-inbox-arrow-down'],
            ] as [$label, $href, $icon])
                <a href="{{ $href }}" class="q-chip"><x-filament::icon :icon="$icon" /> {{ $label }}</a>
            @endforeach
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="q-chip q-chip-gold"><x-filament::icon icon="heroicon-o-arrow-top-right-on-square" /> Open Qurba</a>
        </div>
    </div>
</x-filament-widgets::widget>
