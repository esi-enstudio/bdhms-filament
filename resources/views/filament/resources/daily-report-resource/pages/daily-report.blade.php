<x-filament-panels::page>
{{ $rsoSale }}
    <div class="max-w-6xl mx-auto bg-white shadow-md rounded-lg p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Patwary Telecom - Daily Summary Sheet</h1>
            <div class="flex items-center space-x-2">
                <span class="text-lg">Date:</span>
                <input type="text" class="border rounded px-2 py-1" value="{{ $date }}" readonly>
                <span class="text-lg">2025</span>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">RSO</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">STD</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">RBSP</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">14 Tk</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">19 Tk</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">29Tk D</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">29Tk M</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">69 Tk</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">i-top up</th>
                    <th class="border border-gray-300 px-4 py-2 text-right">Amount</th>
                </tr>
                </thead>
                <tbody>
                <!-- Sample Data -->
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Akash</td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                </tr>
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Counter</td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                </tr>
                <tr class="bg-gray-200 font-bold">
                    <td class="border border-gray-300 px-4 py-2">Total</td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                </tr>
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Lifting</td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                </tr>
                <tr>
                    <td class="border border-gray-300 px-4 py-2">Stock</td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                    <td class="border border-gray-300 px-4 py-2 text-right"></td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Page Number -->
        <div class="text-center mt-4 text-gray-500">
            Page 1
        </div>
    </div>
</x-filament-panels::page>
