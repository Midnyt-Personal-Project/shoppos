@extends('layouts.app')
@section('title', 'License')
@section('page-title', 'License & Activation')

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- ── Current status card ─────────────────────────────────────────── --}}
    @if($status['status'] === 'active')
    <div class="card p-6" style="border-color:rgba(22,163,74,.3)">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
                 style="background:rgba(22,163,74,.15)">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-white font-semibold text-lg">License Active</h2>
                    <span class="badge bg-green-500/10 text-green-400 border border-green-500/20">
                        {{ $status['plan'] }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <p class="text-slate-500 text-xs">Expires On</p>
                        <p class="text-white font-medium mt-0.5">{{ $status['expires_at'] }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs">Days Remaining</p>
                        <p class="font-bold mt-0.5 text-lg
                            {{ $status['days_remaining'] <= 7 ? 'text-amber-400' : 'text-green-400' }}">
                            {{ $status['days_remaining'] }} days
                        </p>
                    </div>
                    @if($details)
                    <div>
                        <p class="text-slate-500 text-xs">License Key</p>
                        <p class="text-slate-400 font-mono text-xs mt-0.5">{{ $details->license_key }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs">Last Verified</p>
                        <p class="text-slate-400 text-xs mt-0.5">{{ $status['verified_at'] ?? '—' }}</p>
                    </div>
                    @endif
                </div>

                @if($status['days_remaining'] <= 14)
                <div class="mt-4 flex items-center gap-3 px-4 py-3 rounded-xl"
                     style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25)">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-amber-300 text-sm">
                        Your license expires soon. <a href="{{ $buyUrl }}" target="_blank" class="font-semibold underline hover:text-amber-200">Renew now →</a>
                    </p>
                </div>
                @endif
            </div>
        </div>

        <div class="mt-4 pt-4 flex items-center gap-3" style="border-top:1px solid rgba(22,163,74,.2)">
            <form method="POST" action="{{ route('license.refresh') }}">
                @csrf
                <button type="submit" class="btn-secondary text-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Re-check with server
                </button>
            </form>
            <a href="{{ $buyUrl }}" target="_blank" class="btn-secondary text-xs">
                🛒 Buy renewal
            </a>
        </div>
    </div>

    @elseif($status['status'] === 'expired')
    <div class="card p-6" style="border-color:rgba(220,38,38,.3)">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
                 style="background:rgba(220,38,38,.15)">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-white font-semibold text-lg">License Expired</h2>
                <p class="text-red-400 text-sm mt-1">{{ $status['message'] }}</p>
                <p class="text-slate-400 text-sm mt-3">
                    Purchase a new license to restore full access. Enter your new key below after payment.
                </p>
                <a href="{{ $buyUrl }}" target="_blank"
                   class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                   style="background:#dc2626" onmouseover="this.style.background='#ef4444'" onmouseout="this.style.background='#dc2626'">
                    🛒 Buy a New License
                </a>
            </div>
        </div>
    </div>

    @else
    <div class="card p-6" style="border-color:rgba(59,130,246,.3)">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
                 style="background:rgba(59,130,246,.15)">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-white font-semibold text-lg">No License Activated</h2>
                <p class="text-slate-400 text-sm mt-1">Enter your license key below to activate OmniPOS.</p>
                <a href="{{ $buyUrl }}" target="_blank" class="text-blue-400 text-sm hover:text-blue-300 underline mt-1 inline-block">
                    Don't have a key? Buy one →
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Activation form ─────────────────────────────────────────────── --}}
    <div class="card p-6">
        <h3 class="text-white font-semibold mb-1">
            {{ $status['status'] === 'active' ? 'Activate a New Key' : 'Enter Your License Key' }}
        </h3>
        <p class="text-slate-500 text-xs mb-5">
            Format: <span class="font-mono text-slate-400">OMNI-XXXX-XXXX-XXXX-XXXX</span>
        </p>

        @if(session('error'))
        <div class="mb-4 flex items-start gap-2.5 px-4 py-3 rounded-xl text-sm"
             style="background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.25);color:#f87171">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
            </svg>
            {{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('license.activate') }}">
            @csrf

            <div class="relative">
                <input type="text"
                       name="license_key"
                       class="input font-mono text-lg tracking-widest text-center py-4"
                       style="font-size:1.1rem;letter-spacing:.15em"
                       placeholder="OMNI-XXXX-XXXX-XXXX-XXXX"
                       autocomplete="off"
                       spellcheck="false"
                       maxlength="24"
                       required>
            </div>

            @error('license_key')
            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="w-full mt-4 py-3 rounded-xl text-sm font-semibold text-white transition-all flex items-center justify-center gap-2"
                    style="background:#16a34a"
                    onmouseover="if(!this.disabled) this.style.background='#22c55e'"
                    onmouseout="this.style.background='#16a34a'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Activate License
            </button>
        </form>
    </div>

    {{-- ── Help ─────────────────────────────────────────────────────────── --}}
    <div class="card p-5 space-y-2"
         style="background:rgba(15,23,42,.5)">
        <p class="text-slate-400 text-sm font-medium">How to get a license key</p>
        <div class="space-y-1.5 text-slate-500 text-xs">
            <p>1. Visit <a href="{{ $buyUrl }}" target="_blank" class="text-blue-400 hover:underline">{{ config('license.buy_url') }}</a> and choose a plan.</p>
            <p>2. Pay via Paystack (card or mobile money). Your key will be emailed to you instantly.</p>
            <p>3. Or contact the developer directly to purchase and receive a key manually.</p>
            <p>4. Enter the key in the box above — it looks like: <span class="font-mono text-slate-400">OMNI-AB12-CD34-EF56-GH78</span></p>
        </div>
    </div>

</div>
@endsection