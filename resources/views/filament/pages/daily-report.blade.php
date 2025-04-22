<x-filament-panels::page>
    <div class="mb-6">
        <x-filament-panels::form wire:model="form">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>
    <div class="space-y-6">
        {!! $this->tableHtmlPage1 !!}
        {!! $this->tableHtmlPage2 !!}
        {!! $this->tableHtmlPage3 !!}
    </div>
</x-filament-panels::page>
