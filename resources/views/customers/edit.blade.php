@extends('layouts.app')
@section('title', 'Edit Customer')
@section('page-title', 'Edit Customer')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="card p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-white font-semibold">Edit Customer</h2>
                <a href="{{ route('customers.show', $customer) }}" class="text-slate-400 hover:text-white text-sm">← Back</a>
            </div>

            <div class="grid grid-cols-1 gap-5">
                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="input">
                    @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" class="input">
                    @error('phone')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="input">
                    @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Address</label>
                    <textarea name="address" rows="2" class="input resize-none">{{ old('address', $customer->address) }}</textarea>
                    @error('address')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-slate-400 text-xs mb-1 block">Credit Limit ({{ auth()->user()->shop->currency_symbol }})</label>
                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" step="0.01" min="0" class="input">
                    @error('credit_limit')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('customers.show', $customer) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Customer</button>
        </div>
    </form>
</div>
@endsection