<x-filament-panels::page>
    <div class="mb-6">
        <x-filament-panels::form wire:model="form">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>
    {!! $tableHtml !!}
</x-filament-panels::page>
