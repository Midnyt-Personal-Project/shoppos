@extends('layouts.app')
@section('title','Edit Expense')
@section('page-title','Edit Expense')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="card p-6 space-y-4">
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Title *</label>
                <input type="text" name="title" value="{{ old('title', $expense->title) }}" required class="input">
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Category</label>
                <select name="category" class="input">
                    <option value="">— Uncategorized —</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(old('category', $expense->category) === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Amount (₵) *</label>
                <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" required step="0.01" min="0.01" class="input">
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Date *</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required class="input">
            </div>
            <div>
                <label class="text-slate-400 text-xs mb-1 block">Notes</label>
                <textarea name="notes" rows="3" class="input resize-none">{{ old('notes', $expense->notes) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('expenses.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Expense</button>
        </div>
    </form>
</div>
@endsection