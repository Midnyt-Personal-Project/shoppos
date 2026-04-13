@extends('layouts.app')

@section('title', 'Dashboard | OmniPOS')

@section('content')
<div class="space-y-8">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-white">Welcome back, {{ auth()->user()->name }}!</h1>
            <p class="text-slate-400 mt-1">Here's what's happening with your store today.</p>
        </div>
        <div>
            <button class="btn-secondary" onclick="window.location.reload()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's Sales -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-2xl font-bold text-white">GHC {{ $todayRevenue }} </span>
            </div>
            <p class="text-slate-400 mt-2">Today's Sales</p>
            <p class="text-xs text-green-400 mt-1">↑ 12% vs yesterday</p>
        </div>

        <!-- Orders Today -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span class="text-2xl font-bold text-white">{{ $todayRevenue }}</span>
            </div>
            <p class="text-slate-400 mt-2">Orders Today</p>
            <p class="text-xs text-green-400 mt-1">↑ 8% vs yesterday</p>
        </div>

        <!-- Total Products -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span class="text-2xl font-bold text-white">{{ $products }}</span>
            </div>
            <p class="text-slate-400 mt-2">Total Products</p>
            <p class="text-xs text-slate-500">Across all categories</p>
        </div>

        <!-- Total Expense -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span class="text-2xl font-bold text-white">GHC {{ $todayExpenses }}</span>
            </div>
            <p class="text-slate-400 mt-2">Total Expense</p>
            <p class="text-xs text-slate-500">Across all Branches</p>
        </div>

        <!-- Active Users -->
        @php
            $admins = $activeUsersByRole['admin'] ?? 0;
            $attendants = $activeUsersByRole['attendance'] ?? 0;
            $totalActive = $admins + $attendants;
        @endphp

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                </svg>
                <span class="text-2xl font-bold text-white">{{ $totalActive }}</span>
            </div>
            <p class="text-slate-400 mt-2">Active Users</p>
            <p class="text-xs text-slate-500">{{ $admins }} admins, {{ $attendants }} attendants</p>
        </div>
    </div>

    <!-- Two-Column Layout: Recent Sales + Low Stock Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Sales -->
        <div class="card">
            <div class="p-6 border-b border-surface-border">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-white">Recent Sales</h2>
                    <a href="{{ route('sales.index') }}" class="text-brand-400 text-sm hover:text-brand-300 transition">View all →</a>
                </div>
            </div>
            <div class="p-6 space-y-4">
                {{-- @for($i = 0; $i < 5; $i++)
                <div class="flex items-center justify-between py-2 border-b border-surface-border last:border-0">
                    <div>
                        <p class="font-medium text-white">#{{ 1000 + $i }} – {{ ['Coffee', 'Sandwich', 'Juice', 'Salad', 'Pastry'][$i] }}</p>
                        <p class="text-xs text-slate-400">By {{ ['John', 'Emma', 'Liam', 'Olivia', 'Noah'][$i] }} • {{ rand(10, 60) }} min ago</p>
                    </div>
                    <span class="font-bold text-white">${{ rand(5, 45) }}.00</span>
                </div>
                @endfor --}}

                @foreach ( $RecentSales as  $sales)
                    <div class="flex items-center justify-between py-2 border-b border-surface-border last:border-0">
                    <div>
                        <p class="font-medium text-white"># {{ $sales->reference }} -  {{ $sales->items->pluck('product_name')->join(', ') }}</p>
                        <p class="text-xs text-slate-400">By {{ $sales->user->name }} ({{$sales->user->role   }}) {{ $sales->created_at }}  min ago</p>
                    </div>
                    <span class="font-bold text-white">${{ rand(5, 45) }}.00</span>
                </div>
                @endforeach($RecentSales)
                
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="card">
            <div class="p-6 border-b border-surface-border">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-white">Low Stock Alerts</h2>
                    <a href="{{ route('products.index') }}" class="text-brand-400 text-sm hover:text-brand-300 transition">Manage inventory →</a>
                </div>
            </div>
            <div class="p-6 space-y-4">
                {{-- @php
                    $lowStockItems = [
                        ['name' => 'HyperDrive Runners', 'stock' => 2, 'threshold' => 10],
                        ['name' => 'Zenith Watch', 'stock' => 1, 'threshold' => 5],
                        ['name' => 'SonicWave Headphones', 'stock' => 0, 'threshold' => 3],
                        ['name' => 'Solaris Aviators', 'stock' => 4, 'threshold' => 8],
                    ];
                @endphp --}}
                @foreach($lowStock as $item)
                <div class="flex items-center justify-between py-2 border-b border-surface-border last:border-0">
                    <div>
                        <p class="font-medium text-white">{{ $item['name'] }}</p>
                        <p class="text-xs text-slate-400">Threshold: {{ $item['threshold'] }}</p>
                    </div>
                    <span class="text-red-400 font-bold">{{ $item['stock'] }} left</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="card">
        <div class="p-6 border-b border-surface-border">
            <h2 class="text-xl font-semibold text-white">Top Selling Products (This Week)</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($topProducts as $item)
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-surface-card flex items-center justify-center">
                    <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-white">{{ $item['product_name'] }}</p>
                    <p class="text-sm text-slate-400"> units sold {{ $item['qty_sold'] }} </p>
                    <p class="text-sm text-slate-400"> units price GHC {{ $item['price'] }} </p>
                </div>
                <span class="font-bold text-white">GHC {{ number_format( $item['revenue'], 2) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection