@extends('layouts.app')
@section('title','Expense Report')
@section('page-title','Expense Report')

@section('content')
<div class="space-y-5">

    {{-- Filters --}}
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            @if($isAdminOrManager)
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Branch</label>
                <select name="branch_id" class="input w-44">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="text-slate-400 text-xs mb-1 block">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-40">
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input w-40">
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Category</label>
                <select name="category" class="input w-44">
                    <option value="">All Categories</option>
                    <option value="uncategorized" @selected(request('category')==='uncategorized')>Uncategorized</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary">Apply Filters</button>
            <a href="{{ route('expenses.report') }}" class="btn-secondary">Clear</a>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card p-5">
            <p class="text-slate-400 text-xs uppercase">Total Expenses</p>
            <p class="text-white text-2xl font-bold mt-1">₵{{ number_format($totalAmount, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-slate-400 text-xs uppercase">Total Transactions</p>
            <p class="text-white text-2xl font-bold mt-1">{{ $expenses->count() }}</p>
        </div>
        <div class="card p-5">
            <p class="text-slate-400 text-xs uppercase">Average Per Transaction</p>
            <p class="text-white text-2xl font-bold mt-1">₵{{ $expenses->count() > 0 ? number_format($totalAmount / $expenses->count(), 2) : '0.00' }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Category Pie Chart --}}
        <div class="card p-5">
            <h3 class="text-white font-semibold mb-4">Expenses by Category</h3>
            <canvas id="categoryChart" height="200"></canvas>
        </div>

        {{-- Monthly/ Daily Chart --}}
        <div class="card p-5">
            <h3 class="text-white font-semibold mb-4">Expenses Over Time</h3>
            <canvas id="timeChart" height="200"></canvas>
        </div>
    </div>

    {{-- Expense List --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-white font-semibold">Expense Details</h3>
            <span class="text-slate-500 text-xs">{{ $expenses->count() }} records</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Title</th>
                    <th class="text-left px-3 py-3">Category</th>
                    <th class="text-left px-3 py-3">Branch</th>
                    <th class="text-left px-3 py-3">By</th>
                    <th class="text-right px-3 py-3">Amount</th>
                    <th class="text-right px-5 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($expenses as $expense)
                <tr class="hover:bg-white/[0.02]">
                    <td class="px-5 py-3">
                        <p class="text-white">{{ $expense->title }}</p>
                        @if($expense->notes)<p class="text-slate-600 text-xs">{{ $expense->notes }}</p>@endif
                    </td>
                    <td class="px-3 py-3">
                        @if($expense->category)
                        <span class="badge bg-slate-700 text-slate-300">{{ ucfirst($expense->category) }}</span>
                        @else
                        <span class="text-slate-500 text-xs italic">Uncategorized</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-slate-400">{{ $expense->branch->name }}</td>
                    <td class="px-3 py-3 text-slate-400">{{ $expense->user->name }}</td>
                    <td class="px-3 py-3 text-right text-amber-400 font-medium">₵{{ number_format($expense->amount, 2) }}</td>
                    <td class="px-5 py-3 text-right text-slate-500 text-xs">{{ $expense->expense_date->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-slate-600">No expenses match the filters</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ── Category Chart ──────────────────────────────────────────────────
        const categoryData = @json($categoryTotals);
        const categoryLabels = Object.keys(categoryData).length ? Object.keys(categoryData) : ['No Data'];
        const categoryValues = Object.keys(categoryData).length ? Object.values(categoryData) : [0];
        const colors = ['#22d3ee', '#34d399', '#fbbf24', '#f472b6', '#a78bfa', '#fb923c', '#60a5fa', '#f87171'];

        const ctx1 = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: categoryLabels.map(l => l || 'Uncategorized'),
                datasets: [{
                    data: categoryValues,
                    backgroundColor: colors.slice(0, categoryLabels.length),
                    borderColor: '#1e293b',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8' } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                let total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = ((ctx.parsed / total) * 100).toFixed(1);
                                return ctx.label + ': ₵' + ctx.parsed.toFixed(2) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // ── Time Chart ──────────────────────────────────────────────────────
        const timeData = @json($dailyTotals->count() > 30 ? $monthlyTotals : $dailyTotals);
        const timeLabels = Object.keys(timeData);
        const timeValues = Object.values(timeData);

        const ctx2 = document.getElementById('timeChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Expenses',
                    data: timeValues,
                    backgroundColor: 'rgba(34, 211, 238, 0.6)',
                    borderColor: '#22d3ee',
                    borderWidth: 2,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: '#94a3b8' } },
                },
                scales: {
                    x: { ticks: { color: '#94a3b8', maxRotation: 45 } },
                    y: { ticks: { color: '#94a3b8' }, beginAtZero: true }
                }
            }
        });
    });
</script>
@endsection