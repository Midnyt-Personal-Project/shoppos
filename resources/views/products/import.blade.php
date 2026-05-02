@extends('layouts.app')
@section('title', 'Import Products')
@section('page-title', 'Import Products from Excel/CSV')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="card p-6 space-y-6">

            {{-- Success with stats --}}
            @if (session('success'))
                <div class="bg-green-500/20 text-green-400 p-4 rounded-lg">
                    <div class="font-bold mb-2">✅ Import Completed</div>
                    <div class="text-sm space-y-1">
                        <p>📦 New products added: <strong>{{ session('stats')['added'] ?? 0 }}</strong></p>
                        <p>🔄 Existing products updated: <strong>{{ session('stats')['updated'] ?? 0 }}</strong></p>
                        <p>⚠️ Skipped (errors): <strong>{{ session('stats')['skipped'] ?? 0 }}</strong></p>
                        <p class="text-blue-300">📊 Total quantity imported:
                            <strong>{{ number_format(session('stats')['total_quantity'] ?? 0) }}</strong> units</p>
                    </div>

                    @if (!empty(session('stats')['details']))
                        <div class="mt-4">
                            <button onclick="toggleDetails()" class="text-xs text-green-300 underline">Show/Hide
                                Details</button>
                            <div id="importDetails" class="hidden mt-3 overflow-x-auto">
                                <table class="w-full text-xs border border-slate-700 rounded">
                                    <thead class="bg-slate-800">
                                        <tr>
                                            <th class="px-2 py-1 text-left">Action</th>
                                            <th class="px-2 py-1 text-left">SKU</th>
                                            <th class="px-2 py-1 text-left">Name</th>
                                            <th class="px-2 py-1 text-right">Old Qty</th>
                                            <th class="px-2 py-1 text-right">Total in DB (after import)</th>
                                            <th class="px-2 py-1 text-right">Old Price</th>
                                            <th class="px-2 py-1 text-right">New Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalInDb = 0; @endphp
                                        @foreach (session('stats')['details'] as $item)
                                            @php $totalInDb += ($item['new_qty'] ?? 0); @endphp
                                            <tr class="border-b border-slate-800">
                                                <td class="px-2 py-1">
                                                    @if ($item['action'] == 'added')
                                                        <span class="text-green-400">Added</span>
                                                    @else
                                                        <span class="text-amber-400">Updated</span>
                                                    @endif
                                                </td>
                                                <td class="px-2 py-1 font-mono">{{ $item['sku'] }}</td>
                                                <td class="px-2 py-1">{{ $item['name'] }}</td>
                                                <td class="px-2 py-1 text-right">{{ $item['old_qty'] ?? '—' }}</td>
                                                <td class="px-2 py-1 text-right font-semibold">
                                                    {{ number_format($item['new_qty'] ?? 0) }}</td>
                                                <td class="px-2 py-1 text-right">
                                                    {{ isset($item['old_price']) ? number_format($item['old_price'], 2) : '—' }}
                                                </td>
                                                <td class="px-2 py-1 text-right">{{ number_format($item['new_price'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-slate-800/50">
                                        <tr>
                                            <td colspan="4" class="px-2 py-1 text-right font-bold">Total quantity in DB
                                                (for these products):</td>
                                            <td class="px-2 py-1 text-right font-bold text-blue-400">
                                                {{ number_format($totalInDb) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Error messages --}}
            @if (session('error'))
                <div class="bg-red-500/20 text-red-400 p-3 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('import_errors'))
                <div class="bg-red-500/20 text-red-400 p-3 rounded-lg text-sm">
                    <strong class="block mb-2">Validation errors:</strong>
                    <ul class="list-disc pl-5 space-y-1 max-h-60 overflow-y-auto">
                        @foreach (session('import_errors') as $failure)
                            <li>Row {{ $failure->row() }}: {{ $failure->errors()[0] }} (Field:
                                {{ $failure->attribute() }})</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Upload form --}}
            <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data"
                class="space-y-5">
                @csrf
                <div>
                    <label class="block text-slate-400 text-sm mb-2">Choose Excel/CSV File</label>
                    <input type="file" name="file" accept=".xlsx,.csv" class="input w-full" required>
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-slate-500 text-xs">Max 2MB. Allowed: .xlsx, .csv</p>
                        <a href="{{ route('products.import.template') }}" class="text-xs text-blue-400 hover:underline flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Excel Template (.xlsx)
                        </a>
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full py-3">Start Import</button>
            </form>

            

            <div class="border-t border-slate-800 pt-5">
                <h3 class="text-white font-semibold text-sm mb-3">📄 Required Excel Columns (header row)</h3>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                    <div><span class="text-green-400">name</span> <span class="text-slate-500">- Product name</span></div>
                    <div><span class="text-green-400">sku</span> <span class="text-slate-500">- Unique SKU</span></div>
                    <div><span class="text-green-400">price</span> <span class="text-slate-500">- Selling price</span></div>
                    <div><span class="text-green-400">cost</span> <span class="text-slate-500">- Cost price</span></div>
                    <div><span class="text-green-400">stock</span> <span class="text-slate-500">- Quantity for your
                            branch</span></div>
                    <div><span class="text-amber-400">category</span> <span class="text-slate-500">- (optional)</span></div>
                    <div><span class="text-amber-400">barcode</span> <span class="text-slate-500">- (optional)</span></div>
                    <div><span class="text-amber-400">description</span> <span class="text-slate-500">- (optional)</span>
                    </div>
                </div>
                <div class="mt-4 bg-slate-800/50 rounded-lg p-3">
                    <p class="text-slate-400 text-xs">
                        <strong class="text-white">📌 Tip:</strong> Products are matched by <strong>SKU</strong>. If SKU
                        exists, the product is <strong class="text-amber-400">updated</strong> (price, cost, stock, etc.).
                        Otherwise, a new product is created.
                    </p>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('products.index') }}" class="text-slate-500 hover:text-white text-sm">← Back to
                    Products</a>
            </div>
        </div>
    </div>

    <script>
        function toggleDetails() {
            const el = document.getElementById('importDetails');
            el.classList.toggle('hidden');
        }

     

    </script>
@endsection
