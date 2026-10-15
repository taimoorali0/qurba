<x-filament-widgets::widget>
    <div class="q-card">
        <p class="q-card-title"><x-filament::icon icon="heroicon-o-server-stack" /> System health</p>
        <p class="q-card-sub">Backups, reminders and background work. Amber means it needs a look.</p>
        <div class="q-rows">
            @foreach ($rows as [$label, $value, $ok])
                <div class="q-row">
                    <span class="q-row-label">{{ $label }}</span>
                    <span class="q-tag {{ $ok ? 'q-ok' : 'q-warn' }}">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
