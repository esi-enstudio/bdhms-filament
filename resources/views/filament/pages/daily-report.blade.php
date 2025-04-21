{{--<x-filament-panels::page>--}}
{{--    <div class="mb-6">--}}
{{--        <x-filament-panels::form wire:model="form">--}}
{{--            {{ $this->form }}--}}
{{--        </x-filament-panels::form>--}}
{{--    </div>--}}
{{--    <div class="space-y-8">--}}
{{--        <div>{!! $this->tableHtmlPage1 !!}</div>--}}
{{--        <div>{!! $this->tableHtmlPage2 !!}</div>--}}
{{--    </div>--}}
{{--</x-filament-panels::page>--}}

<x-filament-panels::page>
    <div class="mb-6">
        <x-filament-panels::form wire:model="form">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>
    <div class="space-y-8">
        <div>{!! $this->tableHtmlPage1 !!}</div>
        <div>{!! $this->tableHtmlPage2 !!}</div>
        <div>{!! $this->tableHtmlPage3 !!}</div>
    </div>
</x-filament-panels::page>
