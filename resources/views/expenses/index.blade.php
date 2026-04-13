@extends('layouts.app')
@section('title','Expenses')
@section('page-title','Expenses')

@section('content')
<div class="space-y-5" x-data="{ showAdd: false }">

    <div class="flex items-center justify-between">
        <div class="card px-4 py-3">
            <span class="text-slate-400 text-xs">Total in period:</span>
            <span class="text-white font-bold ml-2">₵{{ number_format($totalAmount, 2) }}</span>
        </div>
        <button @click="showAdd = true" class="btn-primary">+ Add Expense</button>
    </div>

    <form method="GET" class="flex gap-3 flex-wrap">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input w-40">
        <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="input w-40">
        <select name="category" class="input w-44">
            <option value="">All Categories</option>
            @foreach(['rent','utilities','salaries','supplies','transport','maintenance','general'] as $c)
            <option value="{{ $c }}" @selected(request('category')===$c)>{{ ucfirst($c) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800 text-slate-500 text-xs uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Title</th>
                    <th class="text-left px-3 py-3">Category</th>
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
                    <td class="px-3 py-3"><span class="badge bg-slate-700 text-slate-300">{{ ucfirst($expense->category) }}</span></td>
                    <td class="px-3 py-3 text-slate-400">{{ $expense->user->name }}</td>
                    <td class="px-3 py-3 text-right text-amber-400 font-medium">₵{{ number_format($expense->amount, 2) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="text-slate-500 text-xs">{{ $expense->expense_date->format('d M Y') }}</span>
                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                              class="inline ml-3" onsubmit="return confirm('Delete this expense?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-700 hover:text-red-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-600">No expenses recorded</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-800">{{ $expenses->links() }}</div>
    </div>

    {{-- Add Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6" @click.outside="showAdd = false">
            <h3 class="text-white font-semibold mb-4">Record Expense</h3>
            <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-slate-400 text-xs mb-1 block">Title *</label><input type="text" name="title" required class="input"></div>
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Category *</label>
                    <select name="category" required class="input">
                        @foreach(['rent','utilities','salaries','supplies','transport','maintenance','general'] as $c)
                        <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="text-slate-400 text-xs mb-1 block">Amount (₵) *</label><input type="number" name="amount" required step="0.01" min="0.01" class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Date *</label><input type="date" name="expense_date" required value="{{ today()->toDateString() }}" class="input"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Notes</label><textarea name="notes" rows="2" class="input resize-none"></textarea></div>
                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAdd = false" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection