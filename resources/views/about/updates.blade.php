@extends('layouts.app')

@section('title', 'Updates — OmniPOS')
@section('page-title', 'Update Manager')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="card p-6 mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-white">Current version</h2>
                <p class="text-3xl font-mono text-brand-400 mt-1">{{ config('nativephp.version', '1.0.0') }}</p>
            </div>

            {{-- Standard Laravel form (no AJAX) --}}
            <form method="POST" action="{{ route('updates.check') }}">
                @csrf
                <button type="submit" id="checkUpdatesBtn" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Check for Updates
                </button>
            </form>
        </div>

        {{-- Display flash message if any --}}
        @if(session('success'))
            <div class="mt-4 bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 text-emerald-300">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mt-4 bg-red-500/10 border border-red-500/30 rounded-lg p-4 text-red-300">
                ❌ {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Update History Table --}}
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Update History</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-surface-border">
                        <th class="text-left py-2 text-slate-400 text-sm">Date</th>
                        <th class="text-left py-2 text-slate-400 text-sm">Version Checked</th>
                        <th class="text-left py-2 text-slate-400 text-sm">Status</th>
                        <th class="text-left py-2 text-slate-400 text-sm">Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $record)
                    <tr class="border-b border-surface-border/50">
                        <td class="py-2 text-sm">{{ $record->created_at->format('Y-m-d H:i') }}</td>
                        <td class="py-2 text-sm font-mono">{{ $record->version_checked }}</td>
                        <td class="py-2 text-sm">
                            @if($record->status === 'up-to-date')
                                <span class="badge bg-green-500/20 text-green-400">Up to date</span>
                            @elseif($record->status === 'update-available')
                                <span class="badge bg-yellow-500/20 text-yellow-400">Update available (v{{ $record->new_version }})</span>
                            @elseif($record->status === 'downloaded')
                                <span class="badge bg-blue-500/20 text-blue-400">Downloaded</span>
                            @else
                                <span class="badge bg-red-500/20 text-red-400">{{ ucfirst($record->status) }}</span>
                            @endif
                        </td>
                        <td class="py-2 text-sm text-slate-400">{{ $record->message }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-4 text-center text-slate-500">No update checks recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection