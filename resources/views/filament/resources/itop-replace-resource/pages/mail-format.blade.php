<x-filament-panels::page>
{{--    <div>--}}
{{--        <h1>Mail Format for All Replacements</h1>--}}
{{--        @forelse ($records as $record)--}}
{{--            <div class="mb-4 p-4 border rounded">--}}
{{--                <h2>Replacement #{{ $record->id }}</h2>--}}
{{--                <p><strong>SIM Serial:</strong> {{ $record->sim_serial }}</p>--}}
{{--                <p><strong>Retailer:</strong> {{ $record->retailer->name ?? 'N/A' }}</p>--}}
{{--                <p><strong>Balance:</strong> {{ $record->balance }}</p>--}}
{{--                <p><strong>Reason:</strong> {{ ucfirst($record->reason) }}</p>--}}
{{--                <p><strong>Status:</strong> {{ ucfirst($record->status) }}</p>--}}
{{--                <p><strong>Completed At:</strong> {{ $record->completed_at ?? 'Not Completed' }}</p>--}}
{{--            </div>--}}
{{--        @empty--}}
{{--            <p>No replacements found.</p>--}}
{{--        @endforelse--}}
{{--    </div>--}}
</x-filament-panels::page>
