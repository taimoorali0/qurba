<x-filament-panels::page>
    @php $limit = $this->serverLimitMb(); @endphp
    @if ($limit && $limit < 20)
        <div class="q-card" style="border:1px solid #F1D9A6; background:#FFFBF0">
            <p class="q-card-title"><x-filament::icon icon="heroicon-o-exclamation-triangle" /> Uploads are limited to {{ $limit }} MB on this server</p>
            <p class="q-card-sub" style="margin-top:.5rem">Bigger files fail without a clear message. To allow up to 20 MB, set these in your <b>php.ini</b> and restart the server:</p>
            <pre style="margin-top:.5rem; background:#F7F8F5; padding:.6rem .8rem; border-radius:.6rem; font-size:.8rem">upload_max_filesize = 20M
post_max_size = 25M</pre>
            <p class="q-card-sub" style="margin-top:.4rem">Laragon: Menu → PHP → php.ini, change the two lines, save, then Stop All / Start All (or restart <code>php artisan serve</code>). Or compress the MP3 (mono, 64–96 kbps is plenty).</p>
        </div>
    @endif
    {{-- Sounds: listen, replace, remove --}}
    <x-filament::section icon="heroicon-o-speaker-wave">
        <x-slot name="heading">App sounds</x-slot>
        <x-slot name="description">Listen to what is installed. Upload replacements below — MP3 only, up to {{ $limit && $limit < 20 ? $limit : 20 }} MB.</x-slot>
        <div class="q-sounds">
            @foreach ($this->sounds() as $s)
                <div class="q-card">
                    <div class="q-health-top">
                        <b>{{ $s['label'] }}</b>
                        <span class="q-tag {{ $s['url'] ? 'q-ok' : 'q-warn' }}">{{ $s['url'] ? 'Added' : 'Missing' }}</span>
                    </div>
                    @if ($s['url'])
                        <audio controls preload="none" src="{{ $s['url'] }}"></audio>
                        <x-filament::link tag="button" color="danger" size="sm" wire:click="deleteSound('{{ $s['file'] }}')"
                            wire:confirm="Remove {{ $s['label'] }}?">Remove</x-filament::link>
                    @else
                        <p class="q-card-sub">Not uploaded yet.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">Upload</x-filament::button>
    </form>

    {{-- 99 Names grid --}}
    @php $names = $this->names(); $have = collect($names)->whereNotNull('url')->count(); @endphp
    <x-filament::section icon="heroicon-o-sparkles">
        <x-slot name="heading">99 Names of Allah — {{ $have }} / 99 recorded</x-slot>
        <x-slot name="description">Listen, replace or remove each recording. To replace one, choose an MP3 on its card.</x-slot>
        <div class="q-bar" style="margin-bottom:1rem"><span style="width: {{ round($have / 99 * 100) }}%"></span></div>
        <div class="q-names">
            @foreach ($names as $x)
                <div class="q-name {{ $x['url'] ? 'has' : '' }}" wire:key="name-{{ $x['n'] }}">
                    <div class="q-health-top">
                        <span class="q-name-num">#{{ $x['n'] }}</span>
                        <span class="q-tag {{ $x['url'] ? 'q-ok' : 'q-warn' }}">{{ $x['url'] ? '✓' : '—' }}</span>
                    </div>
                    <div class="q-name-ar">{{ $x['ar'] }}</div>
                    <div class="q-name-tr">{{ $x['tr'] }}</div>
                    @if ($x['url'])
                        <audio controls preload="none" src="{{ $x['url'] }}"></audio>
                    @endif
                    <label class="q-name-tr" style="cursor:pointer; color:#14654D">
                        <input type="file" accept="audio/mpeg,.mp3" wire:model="nameUploads.{{ $x['n'] }}" style="display:none">
                        <span wire:loading.remove wire:target="nameUploads.{{ $x['n'] }}">{{ $x['url'] ? 'Replace' : 'Upload' }}</span>
                        <span wire:loading wire:target="nameUploads.{{ $x['n'] }}">Uploading…</span>
                    </label>
                    @error('nameUploads.' . $x['n']) <span class="q-name-tr" style="color:#a03a2c">{{ $message }}</span> @enderror
                    @if ($x['url'])
                        <x-filament::link tag="button" size="xs" wire:click="useAsFull({{ $x['n'] }})"
                            wire:confirm="Is this one recording of all 99 names? It will move to the Complete 99 Names slot.">Use as complete recitation</x-filament::link>
                        <x-filament::link tag="button" color="danger" size="xs" wire:click="deleteName({{ $x['n'] }})" wire:confirm="Remove the recording for {{ $x['tr'] }}?">Remove</x-filament::link>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
