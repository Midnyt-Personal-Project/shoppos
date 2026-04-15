{{-- resources/views/updates.blade.php --}}
@extends('layouts.app')

@section('title', 'Updates — OmniPOS')
@section('page-title', 'Update Manager')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="card p-6 mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-white">Current version</h2>
                <p class="text-3xl font-mono text-brand-400 mt-1">{{ config('app.version', '1.0.0') }}</p>
            </div>
            <button id="checkUpdatesBtn" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Check for Updates
            </button>
        </div>

        <div id="updateResult" class="mt-4 hidden"></div>
    </div>

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
                            @else
                                <span class="badge bg-red-500/20 text-red-400">Error</span>
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

<script>
    document.getElementById('checkUpdatesBtn')?.addEventListener('click', async function() {
        const btn = this;
        const resultDiv = document.getElementById('updateResult');

        // Disable button and show spinner
        btn.disabled = true;
        btn.innerHTML = `<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                         </svg> Checking...`;

        resultDiv.classList.add('hidden');
        resultDiv.innerHTML = '';

        try {
            const response = await fetch('{{ route("updates.check") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await response.json();

            if (data.success) {
                if (data.status === 'update-available') {
                    resultDiv.innerHTML = `
                        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 text-yellow-300">
                            <strong>New version ${data.latest} available!</strong><br>
                            Current: ${data.current}<br>
                            <button id="downloadUpdateBtn" class="mt-2 btn-primary text-sm py-1">Download Update</button>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 text-emerald-300">
                            ✅ You are running the latest version (${data.current}).
                        </div>
                    `;
                }
                resultDiv.classList.remove('hidden');

                // Optionally reload the history table (simple way: reload page)
                setTimeout(() => location.reload(), 1500);
            } else {
                resultDiv.innerHTML = `<div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 text-red-300">❌ Error: ${data.error}</div>`;
                resultDiv.classList.remove('hidden');
            }
        } catch (err) {
            resultDiv.innerHTML = `<div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 text-red-300">❌ Network error: ${err.message}</div>`;
            resultDiv.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg> Check for Updates`;
        }
    });
</script>
@endsection