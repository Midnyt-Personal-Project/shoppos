@extends('layouts.app')
@section('title','Sales Report')
@section('page-title','Sales Report')

@section('content')
<div class="space-y-5">

    {{-- Date filters --}}
    <form method="GET" class="flex gap-3 items-end flex-wrap card p-4">
        <div>
            <label class="text-slate-500 text-xs mb-1 block">From</label>
            <input type="date" name="date_from" value="{{ $from }}" class="input w-40">
        </div>
        <div>
            <label class="text-slate-500 text-xs mb-1 block">To</label>
            <input type="date" name="date_to" value="{{ $to }}" class="input w-40">
        </div>
        <button type="submit" class="btn-primary">Apply</button>
        <div class="flex gap-2 ml-auto">
            @foreach(['Today' => [today(), today()], 'This Week' => [now()->startOfWeek(), now()], 'This Month' => [now()->startOfMonth(), now()]] as $label => $range)
            <a href="{{ route('reports.sales', ['date_from' => $range[0]->toDateString(), 'date_to' => $range[1]->toDateString()]) }}"
               class="btn-secondary text-xs">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $kpis = [
                ['Revenue',   number_format($revenue, 2),  'text-green-400'],
                ['COGS',      number_format($cogs, 2),     'text-red-400'],
                ['Expenses',  number_format($expenses, 2), 'text-amber-400'],
                ['Net Profit',number_format($profit, 2),   $profit >= 0 ? 'text-green-400' : 'text-red-400'],
            ];
        @endphp
        @foreach($kpis as [$label, $value, $color])
        <div class="card p-5">
            <p class="text-slate-500 text-xs">{{ $label }}</p>
            <p class="text-xl font-bold {{ $color }} mt-1">{{ auth()->user()->shop->currency_symbol }}{{ $value }}</p>
        </div>
        @endforeach
    </div>

    {{-- Chart with fixed height container --}}
    @if($chartDates->count() > 1)
    <div class="card p-5">
        <h2 class="text-white font-semibold text-sm mb-4">Daily Revenue</h2>
        <div style="height: 300px; position: relative;">
            <canvas id="salesChart" style="width: 100%; height: 100%;"></canvas>
        </div>
    </div>
    @endif

    {{-- Top products table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800">
            <h2 class="text-white font-semibold text-sm">Top Products (this period)</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                    <th class="text-left px-5 py-2">#</th>
                    <th class="text-left px-3 py-2">Product</th>
                    <th class="text-right px-3 py-2">Qty Sold</th>
                    <th class="text-right px-3 py-2">Revenue</th>
                    <th class="text-right px-5 py-2">Profit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($topProducts as $i => $product)
                <tr>
                    <td class="px-5 py-3 text-slate-600 text-xs">{{ $i + 1 }}</td>
                    <td class="px-3 py-3 text-white">{{ $product->product_name }}</td>
                    <td class="px-3 py-3 text-right text-slate-300">{{ number_format($product->qty_sold, 0) }}</td>
                    <td class="px-3 py-3 text-right text-green-400 font-medium">{{ auth()->user()->shop->currency_symbol }}{{ number_format($product->revenue, 2) }}</td>
                    <td class="px-5 py-3 text-right text-purple-400">{{ auth()->user()->shop->currency_symbol }}{{ number_format($product->profit, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-600">No sales data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginated transactions table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-white font-semibold text-sm">Transactions ({{ $sales->total() }})</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-800">
                    <th class="text-left px-5 py-2">Reference</th>
                    <th class="text-left px-3 py-2">Customer</th>
                    <th class="text-right px-3 py-2">Total</th>
                    <th class="text-right px-3 py-2">Profit</th>
                    <th class="text-right px-5 py-2">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($sales as $sale)
                <tr class="hover:bg-white/[0.02] cursor-pointer" onclick="window.location='{{ route('sales.show', $sale) }}'">
                    <td class="px-5 py-2 font-mono text-xs text-green-400">{{ $sale->reference }}</td>
                    <td class="px-3 py-2 text-slate-400">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                    <td class="px-3 py-2 text-right text-white">{{ auth()->user()->shop->currency_symbol }}{{ number_format($sale->total, 2) }}</td>
                    <td class="px-3 py-2 text-right text-purple-400">{{ auth()->user()->shop->currency_symbol }}{{ number_format($sale->profit(), 2) }}</td>
                    <td class="px-5 py-2 text-right text-slate-500 text-xs">{{ $sale->created_at->format('d M H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-600">No sales in this period</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-800">
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($chartDates->count() > 1)
        // Destroy existing chart if any
        if (window.salesChartInstance) {
            window.salesChartInstance.destroy();
        }

        const ctx = document.getElementById('salesChart').getContext('2d');
        window.salesChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartDates->values()),
                datasets: [{
                    label: 'Revenue',
                    data: @json($chartValues->values()),
                    backgroundColor: 'rgba(34,197,94,0.3)',
                    borderColor: '#22c55e',
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,  // respects parent height
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: {{ auth()->user()->shop->currency_symbol }}' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { color: '#64748b', font: { size: 10 } }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: {
                            color: '#64748b',
                            font: { size: 10 },
                            callback: function(value) {
                                return '{{ auth()->user()->shop->currency_symbol }}' + value.toLocaleString();
                            }
                        },
                        beginAtZero: true
                    }
                }
            }
        });
        @endif
    });
</script>
@endpush