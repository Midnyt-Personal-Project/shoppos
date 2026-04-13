@extends('layouts.app')
@section('title','Products')
@section('page-title','Products')

@section('content')
<div class="space-y-5" x-data="{
    restockModal:  false,
    transferModal: false,
    removeModal:   false,
    activeProduct: null,
    activeProductName: '',
    restockBranch: '',
    restockQty:    1,
    fromBranch:    '',
    toBranch:      '',
    transferQty:   1,
    transferNotes: '',
    removeBranch:  '',
    processing:    false,

    openRestock(productId, productName, defaultBranch) {
        this.activeProduct      = productId;
        this.activeProductName  = productName;
        this.restockBranch      = defaultBranch;
        this.restockQty         = 1;
        this.restockModal       = true;
    },
    openTransfer(productId, productName, defaultFrom) {
        this.activeProduct      = productId;
        this.activeProductName  = productName;
        this.fromBranch         = defaultFrom;
        this.toBranch           = '';
        this.transferQty        = 1;
        this.transferNotes      = '';
        this.transferModal      = true;
    },
    openRemove(productId, productName, branchId) {
        this.activeProduct      = productId;
        this.activeProductName  = productName;
        this.removeBranch       = branchId;
        this.removeModal        = true;
    },

    async doRestock() {
        if (!this.restockBranch || this.restockQty <= 0) return;
        this.processing = true;
        const res = await fetch('/products/' + this.activeProduct + '/restock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ branch_id: this.restockBranch, quantity: this.restockQty }),
        });
        const d = await res.json();
        this.processing = false;
        if (d.success) { this.restockModal = false; location.reload(); }
        else alert(d.message);
    },

    async doTransfer() {
        if (!this.fromBranch || !this.toBranch || this.fromBranch === this.toBranch) {
            alert('Please select two different branches.'); return;
        }
        this.processing = true;
        const res = await fetch('/products/' + this.activeProduct + '/transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ from_branch_id: this.fromBranch, to_branch_id: this.toBranch, quantity: this.transferQty, notes: this.transferNotes }),
        });
        const d = await res.json();
        this.processing = false;
        if (d.success) { this.transferModal = false; location.reload(); }
        else alert(d.message);
    },

    async doRemove() {
        this.processing = true;
        const res = await fetch('/products/' + this.activeProduct + '/remove-branch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ branch_id: this.removeBranch }),
        });
        const d = await res.json();
        this.processing = false;
        if (d.success) { this.removeModal = false; location.reload(); }
        else alert(d.message);
    },
}">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <p class="text-slate-400 text-sm">Manage products and branch stock levels</p>
        <a href="{{ route('products.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Product
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, barcode, SKU…" class="input w-64">
        <select name="category" class="input w-44">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
        <select name="status" class="input w-36">
            <option value="">All Status</option>
            <option value="active"   @selected(request('status')==='active')>Active</option>
            <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
        </select>
        @if(auth()->user()->isAdmin())
        <select name="branch_filter" class="input w-44">
            <option value="all"  @selected(request('branch_filter') !== 'mine')>All Branches</option>
            <option value="mine" @selected(request('branch_filter') === 'mine')>My Branch Only</option>
        </select>
        @endif
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['search','category','status','branch_filter']))
        <a href="{{ route('products.index') }}" class="btn-secondary">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Product</th>
                    <th class="text-right px-3 py-3">Price / Cost</th>
                    <th class="text-left px-3 py-3">Branch Stock Levels</th>
                    <th class="text-center px-3 py-3">Status</th>
                    <th class="text-right px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($products as $product)
                <tr class="hover:bg-white/[0.02] transition-colors">

                    {{-- Product info --}}
                    <td class="px-5 py-4">
                        <div class="font-medium text-white">{{ $product->name }}</div>
                        <div class="text-slate-600 text-xs font-mono mt-0.5">{{ $product->barcode ?: ($product->sku ?: '—') }}</div>
                        @if($product->category)
                        <span class="mt-1 inline-block badge bg-slate-700/60 text-slate-400 text-[10px]">{{ $product->category }}</span>
                        @endif
                    </td>

                    {{-- Price --}}
                    <td class="px-3 py-4 text-right">
                        <div class="text-green-400 font-medium">₵{{ number_format($product->price, 2) }}</div>
                        <div class="text-slate-600 text-xs">cost ₵{{ number_format($product->cost, 2) }}</div>
                    </td>

                    {{-- Branch stock breakdown --}}
                    <td class="px-3 py-4">
                        @if($product->stocks->isEmpty())
                            <span class="text-slate-600 text-xs italic">Not assigned to any branch</span>
                        @else
                        <div class="space-y-1.5">
                            @foreach($product->stocks as $stock)
                            <div class="flex items-center gap-2">
                                {{-- Branch dot + name --}}
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0
                                        {{ $stock->isOutOfStock() ? 'bg-red-500' : ($stock->isLow() ? 'bg-amber-500' : 'bg-green-500') }}">
                                    </div>
                                    <span class="text-slate-400 text-xs truncate max-w-[100px]" title="{{ $stock->branch->name }}">
                                        {{ $stock->branch->name }}
                                    </span>
                                </div>

                                {{-- Stock qty badge --}}
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded
                                    {{ $stock->isOutOfStock()
                                        ? 'bg-red-500/10 text-red-400'
                                        : ($stock->isLow()
                                            ? 'bg-amber-500/10 text-amber-400'
                                            : 'bg-green-500/10 text-green-400') }}">
                                    {{ number_format($stock->quantity, 0) }}
                                    {{ $product->unit }}
                                </span>

                                {{-- Inline quick-restock button --}}
                                @if($stock->branch_id === $branchId || auth()->user()->isAdmin())
                                <button
                                    @click="openRestock({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $stock->branch_id }}')"
                                    class="text-slate-600 hover:text-green-400 transition-colors"
                                    title="Quick restock {{ $stock->branch->name }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td class="px-3 py-4 text-center">
                        <span class="badge {{ $product->is_active ? 'bg-green-500/10 text-green-400' : 'bg-slate-700 text-slate-500' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-1">

                            {{-- Restock --}}
                            <button
                                @click="openRestock({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $branchId }}')"
                                class="px-2.5 py-1.5 text-xs text-blue-400 hover:text-blue-300 hover:bg-blue-400/10 rounded-lg transition-colors font-medium"
                                title="Add stock">
                                + Stock
                            </button>

                            {{-- Transfer (only admins/owners with multiple branches) --}}
                            @if(auth()->user()->isAdmin() && $branches->count() > 1)
                            <button
                                @click="openTransfer({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $branchId }}')"
                                class="px-2.5 py-1.5 text-xs text-purple-400 hover:text-purple-300 hover:bg-purple-400/10 rounded-lg transition-colors font-medium"
                                title="Move stock between branches">
                                Transfer
                            </button>
                            @endif

                            {{-- Edit --}}
                            <a href="{{ route('products.edit', $product) }}"
                               class="p-1.5 text-slate-500 hover:text-white transition-colors rounded-lg hover:bg-white/5"
                               title="Edit product">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>

                            {{-- Deactivate --}}
                            <form method="POST" action="{{ route('products.destroy', $product) }}"
                                  onsubmit="return confirm('Deactivate this product?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-slate-500 hover:text-red-400 transition-colors rounded-lg hover:bg-red-400/10"
                                        title="Deactivate">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-slate-600">
                        No products found. <a href="{{ route('products.create') }}" class="text-green-500 hover:underline">Add your first product →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-800">{{ $products->links() }}</div>
    </div>

    {{-- ── Restock Modal ──────────────────────────────────────────────── --}}
    <div x-show="restockModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6 space-y-4" @click.outside="restockModal = false">
            <div>
                <h3 class="text-white font-semibold">Add Stock</h3>
                <p class="text-slate-500 text-xs mt-0.5" x-text="activeProductName"></p>
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-1 block">Branch *</label>
                <select x-model="restockBranch" class="input">
                    <option value="">— Select Branch —</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-1 block">Quantity to Add *</label>
                <input type="number" x-model.number="restockQty" min="0.01" step="0.01" class="input">
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="restockModal = false" class="btn-secondary flex-1">Cancel</button>
                <button @click="doRestock()" :disabled="processing"
                        class="btn-primary flex-1" x-text="processing ? 'Saving…' : 'Add Stock'"></button>
            </div>
        </div>
    </div>

    {{-- ── Transfer Modal ─────────────────────────────────────────────── --}}
    <div x-show="transferModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-[420px] p-6 space-y-4" @click.outside="transferModal = false">
            <div>
                <h3 class="text-white font-semibold">Transfer Stock Between Branches</h3>
                <p class="text-slate-500 text-xs mt-0.5" x-text="activeProductName"></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">From Branch *</label>
                    <select x-model="fromBranch" class="input">
                        <option value="">— Select —</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">To Branch *</label>
                    <select x-model="toBranch" class="input">
                        <option value="">— Select —</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" :disabled="'{{ $branch->id }}' == fromBranch">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-1 block">Quantity to Transfer *</label>
                <input type="number" x-model.number="transferQty" min="0.01" step="0.01" class="input">
            </div>

            <div>
                <label class="text-slate-400 text-xs mb-1 block">Notes (optional)</label>
                <input type="text" x-model="transferNotes" class="input" placeholder="e.g. Weekly replenishment">
            </div>

            <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg px-3 py-2">
                <p class="text-amber-400 text-xs">Stock will be deducted from the source branch and added to the destination. This cannot be undone.</p>
            </div>

            <div class="flex gap-3 pt-1">
                <button @click="transferModal = false" class="btn-secondary flex-1">Cancel</button>
                <button @click="doTransfer()" :disabled="processing"
                        class="btn-primary flex-1" x-text="processing ? 'Transferring…' : 'Confirm Transfer'"></button>
            </div>
        </div>
    </div>

    {{-- ── Remove from Branch Modal ────────────────────────────────────── --}}
    <div x-show="removeModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6 space-y-4" @click.outside="removeModal = false">
            <h3 class="text-white font-semibold">Remove Product from Branch?</h3>
            <p class="text-slate-400 text-sm">
                This will remove <strong x-text="activeProductName" class="text-white"></strong> from the selected branch.
                The product will no longer appear in that branch's POS screen.
            </p>
            <div class="flex gap-3">
                <button @click="removeModal = false" class="btn-secondary flex-1">Cancel</button>
                <button @click="doRemove()" :disabled="processing"
                        class="btn-danger flex-1" x-text="processing ? 'Removing…' : 'Yes, Remove'"></button>
            </div>
        </div>
    </div>

</div>
@endsection