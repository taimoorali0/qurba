<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Installed</x-slot>
        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($this->status() as $label => $value)
                <div>
                    <dt class="text-sm text-gray-500">{{ $label }}</dt>
                    <dd class="font-medium">
                        @if (is_bool($value)) {{ $value ? '✓ Added' : '— Missing' }} @else {{ $value }} @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Save</x-filament::button>
    </form>
</x-filament-panels::page>
