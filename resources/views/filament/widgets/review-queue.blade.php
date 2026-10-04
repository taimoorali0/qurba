<x-filament-widgets::widget>
    <div class="q-card">
        <p class="q-card-title"><x-filament::icon icon="heroicon-o-queue-list" /> Review queue</p>
        <p class="q-card-sub">Nothing below is visible to users until it is approved.</p>
        <div class="q-rows">
            @foreach ($rows as [$label, $hint, $count, $href, $icon])
                <a href="{{ $href }}" class="q-row">
                    <span class="q-row-icon {{ $count ? 'q-warn' : 'q-ok' }}"><x-filament::icon :icon="$icon" /></span>
                    <span class="q-row-label">{{ $label }}<small>{{ $hint }}</small></span>
                    <span class="q-count">{{ $count ?: '✓' }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
