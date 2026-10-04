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

    {{-- 99 Names grid, one tab per voice --}}
    @php $names = $this->names(); $have = collect($names)->whereNotNull('url')->count(); $voices = \App\Support\NameVoices::ALL; $full = $this->fullUrl(); @endphp
    <x-filament::section icon="heroicon-o-sparkles">
        <x-slot name="heading">99 Names of Allah — {{ $voices[$voice]['label'] }} voice: {{ $have }} / 99 recorded</x-slot>
        <x-slot name="description">Each voice (for example a children's voice) has its own recordings. Pick a voice, then upload per name or one complete recitation.</x-slot>
        <x-filament::tabs style="margin-bottom:1rem">
            @foreach ($voices as $key => $v)
                <x-filament::tabs.item :active="$voice === $key" wire:click="setVoice('{{ $key }}')" icon="{{ $key === 'kids' ? 'heroicon-o-face-smile' : 'heroicon-o-microphone' }}">
                    {{ $v['label'] }} <span class="q-tag q-ok" style="margin-inline-start:.35rem">{{ count(\App\Support\NameVoices::recorded($key)) }}</span>
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        <div class="q-card" style="margin-bottom:1rem">
            <div class="q-health-top">
                <b>Complete recitation — {{ $voices[$voice]['label'] }}</b>
                <span class="q-tag {{ $full ? 'q-ok' : 'q-warn' }}">{{ $full ? 'Added' : 'Missing' }}</span>
            </div>
            <p class="q-card-sub">One recording with all 99 names, played by “Play all” when this voice is chosen.</p>
            @if ($full)<audio controls preload="none" src="{{ $full }}" style="width:100%; margin-top:.6rem"></audio>@endif
            <div style="display:flex; gap:1rem; margin-top:.5rem; align-items:center">
                <label style="cursor:pointer; color:#14654D; font-size:.85rem">
                    <input type="file" accept="audio/mpeg,.mp3" wire:model="fullUpload" style="display:none">
                    <span wire:loading.remove wire:target="fullUpload">{{ $full ? 'Replace' : 'Upload' }} complete recitation</span>
                    <span wire:loading wire:target="fullUpload">Uploading…</span>
                </label>
                @if ($full)<x-filament::link tag="button" color="danger" size="sm" wire:click="deleteFull" wire:confirm="Remove the complete recitation for this voice?">Remove</x-filament::link>@endif
            </div>
            @error('fullUpload') <p class="q-card-sub" style="color:#a03a2c">{{ $message }}</p> @enderror
        </div>

        <div class="q-bar" style="margin-bottom:1rem"><span style="width: {{ round($have / 99 * 100) }}%"></span></div>
        <div class="q-names">
            @foreach ($names as $x)
                <div class="q-name {{ $x['url'] ? 'has' : '' }}" wire:key="name-{{ $voice }}-{{ $x['n'] }}">
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
