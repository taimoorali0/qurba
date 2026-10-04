<x-filament-widgets::widget>
    <div class="q-card">
        <p class="q-card-title"><x-filament::icon icon="heroicon-o-heart" /> Content health</p>
        <p class="q-card-sub">What users can see today.</p>
        <div class="q-health">
            @foreach ($bars as [$label, $have, $total, $unit])
                @php $pct = $total ? min(100, round($have / $total * 100)) : 0; @endphp
                <div>
                    <div class="q-health-top"><span>{{ $label }}</span><b>{{ number_format($have) }} / {{ number_format($total) }} {{ $unit }}</b></div>
                    <div class="q-bar"><span style="width: {{ $pct }}%"></span></div>
                </div>
            @endforeach
        </div>
        <div class="q-rows">
            @foreach ($facts as [$label, $value])
                <div class="q-row">
                    <span class="q-row-label">{{ $label }}</span>
                    <span class="q-tag {{ str_contains($value, 'Missing') || str_starts_with($value, '0 approved') ? 'q-warn' : 'q-ok' }}">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
