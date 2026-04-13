@extends('layouts.app')
@section('title','Branches')
@section('page-title','Branch Management')

@section('content')
<div class="space-y-5" x-data="{ showAdd: false }">

    <div class="flex items-center justify-between">
        <p class="text-slate-400 text-sm">{{ $branches->count() }} branch(es)</p>
        <button @click="showAdd = true" class="btn-primary">+ Add Branch</button>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($branches as $branch)
        <div class="card p-5 space-y-3">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-white font-semibold">{{ $branch->name }}</h3>
                    <p class="text-slate-500 text-xs mt-0.5">{{ $branch->address ?: 'No address' }}</p>
                </div>
                <span class="badge {{ $branch->is_active ? 'bg-green-500/10 text-green-400' : 'bg-slate-700 text-slate-500' }}">
                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-800">
                <div>
                    <p class="text-slate-600 text-xs">Staff</p>
                    <p class="text-white font-medium">{{ $branch->users_count }}</p>
                </div>
                <div>
                    <p class="text-slate-600 text-xs">Phone</p>
                    <p class="text-white font-medium text-xs">{{ $branch->phone ?: '—' }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Add Branch Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="card w-96 p-6" @click.outside="showAdd = false">
            <h3 class="text-white font-semibold mb-4">Add Branch</h3>
            <form method="POST" action="{{ route('branches.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-slate-400 text-xs mb-1 block">Branch Name *</label><input type="text" name="name" required class="input" placeholder="e.g. Kumasi Branch"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Address</label><input type="text" name="address" class="input" placeholder="Location or address"></div>
                <div><label class="text-slate-400 text-xs mb-1 block">Phone</label><input type="tel" name="phone" class="input"></div>
                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAdd = false" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection