@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="max-w-4xl" x-data="{ tab: 'general' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 mb-6 bg-slate-800/50 p-1 rounded-xl w-fit">
        @foreach(['general' => 'General', 'email' => 'Email & Gmail', 'notifications' => 'Notifications'] as $key => $label)
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}'
                    ? 'bg-surface-card text-white shadow-sm'
                    : 'text-slate-500 hover:text-slate-300'"
                class="px-5 py-2 rounded-lg text-sm font-medium transition-all">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ═══ TAB: GENERAL ════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'general'" x-cloak>
        <form method="POST" action="{{ route('settings.general') }}" class="space-y-5">
            @csrf

            <div class="card p-6 space-y-5">
                <h2 class="text-white font-semibold border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Shop Information
                </h2>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="text-slate-400 text-xs mb-1 block">Shop / Business Name *</label>
                        <input type="text" name="shop_name" value="{{ old('shop_name', $shopSettings['shop_name']) }}"
                               required class="input" placeholder="e.g. Mensah's Supermarket">
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Contact Phone</label>
                        <input type="text" name="shop_phone" value="{{ old('shop_phone', $shopSettings['shop_phone']) }}"
                               class="input" placeholder="e.g. 0244000000">
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Contact Email</label>
                        <input type="email" name="shop_email" value="{{ old('shop_email', $shopSettings['shop_email']) }}"
                               class="input" placeholder="e.g. info@yourshop.com">
                    </div>
                    <div class="col-span-2">
                        <label class="text-slate-400 text-xs mb-1 block">Address</label>
                        <input type="text" name="shop_address" value="{{ old('shop_address', $shopSettings['shop_address']) }}"
                               class="input" placeholder="e.g. Accra Central, Greater Accra">
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-5">
                <h2 class="text-white font-semibold border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Currency
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Currency Code *</label>
                        <input type="text" name="currency" value="{{ old('currency', $shopSettings['currency']) }}"
                               required maxlength="10" class="input font-mono" placeholder="e.g. GHS">
                        <p class="text-slate-600 text-xs mt-1">3-letter ISO code (GHS, USD, NGN, KES…)</p>
                    </div>
                    <div>
                        <label class="text-slate-400 text-xs mb-1 block">Currency Symbol *</label>
                        <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $shopSettings['currency_symbol']) }}"
                               required maxlength="5" class="input font-mono w-32" placeholder="e.g. ₵">
                        <p class="text-slate-600 text-xs mt-1">Symbol shown on receipts (₵, $, ₦, KSh…)</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary px-8">Save General Settings</button>
            </div>
        </form>
    </div>

    {{-- ═══ TAB: EMAIL ══════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'email'" x-cloak class="space-y-6">

        {{-- Explainer card --}}
        <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-5">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-blue-300 font-medium text-sm">How Branch Email Works</p>
                    <p class="text-blue-400/80 text-xs mt-1 leading-relaxed">
                        Each branch uses its <strong>own Gmail account</strong> to send emails — low stock alerts,
                        daily summaries, and customer receipts will be sent <em>from that branch's Gmail</em>.
                        You need a <strong>Gmail App Password</strong>, not your regular Gmail password.
                    </p>
                    <a href="https://myaccount.google.com/apppasswords" target="_blank"
                       class="inline-flex items-center gap-1 text-blue-400 text-xs mt-2 hover:text-blue-300 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        How to create a Gmail App Password →
                    </a>
                </div>
            </div>
        </div>

        {{-- Per-branch email config cards --}}
        @foreach($branches as $branch)
        @php
            $bConfig = $branchMailConfigs[$branch->id] ?? [];
            $hasEmail = !empty($bConfig['mail_gmail_address']);
            $isEnabled = !empty($bConfig['mail_enabled']);
        @endphp

        <div class="card overflow-hidden"
             x-data="{
                 open: {{ $hasEmail ? 'true' : 'false' }},
                 testing: false,
                 testTo: '',
                 testResult: null,
                 showPassword: false,
                 async sendTest() {
                     if (!this.testTo) { alert('Enter an email address to send the test to.'); return; }
                     this.testing = true;
                     this.testResult = null;
                     const res = await fetch('{{ route('settings.testEmail', $branch) }}', {
                         method: 'POST',
                         headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({ test_to: this.testTo })
                     });
                     this.testResult = await res.json();
                     this.testing = false;
                 }
             }">

            {{-- Branch header --}}
            <div class="flex items-center justify-between px-5 py-4 cursor-pointer select-none"
                 @click="open = !open">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full {{ $hasEmail && $isEnabled ? 'bg-green-500' : ($hasEmail ? 'bg-amber-500' : 'bg-slate-600') }}"></div>
                    <div>
                        <p class="text-white font-medium text-sm">{{ $branch->name }}</p>
                        <p class="text-slate-500 text-xs">
                            @if($hasEmail)
                                {{ $bConfig['mail_gmail_address'] }}
                                <span class="ml-2 {{ $isEnabled ? 'text-green-500' : 'text-amber-500' }}">
                                    • {{ $isEnabled ? 'Active' : 'Paused' }}
                                </span>
                            @else
                                <span class="text-slate-600">No Gmail configured</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($hasEmail)
                    <span class="badge bg-green-500/10 text-green-400 text-xs">Configured</span>
                    @else
                    <span class="badge bg-slate-700 text-slate-500 text-xs">Not set</span>
                    @endif
                    <svg class="w-4 h-4 text-slate-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            {{-- Expanded form --}}
            <div x-show="open" x-cloak class="border-t border-slate-800">
                <form method="POST" action="{{ route('settings.saveBranchEmail', $branch) }}" class="p-5 space-y-4">
                    @csrf

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="text-slate-400 text-xs mb-1 block">Gmail Address *</label>
                            <input type="email" name="mail_gmail_address"
                                   value="{{ old('mail_gmail_address', $bConfig['mail_gmail_address'] ?? '') }}"
                                   required class="input" placeholder="branchemail@gmail.com"
                                   autocomplete="off">
                            <p class="text-slate-600 text-xs mt-1">Must be a Gmail address (ends in @gmail.com)</p>
                        </div>

                        <div class="col-span-2">
                            <label class="text-slate-400 text-xs mb-1 block">Gmail App Password *</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                       name="mail_gmail_app_password"
                                       value="{{ old('mail_gmail_app_password', $bConfig['mail_gmail_app_password'] ?? '') }}"
                                       required class="input pr-10 font-mono tracking-widest"
                                       placeholder="xxxx xxxx xxxx xxxx"
                                       autocomplete="new-password">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-slate-600 text-xs mt-1">
                                This is a 16-character App Password from Google — NOT your Gmail login password.
                                <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-green-500 hover:underline">Get one here →</a>
                            </p>
                        </div>

                        <div>
                            <label class="text-slate-400 text-xs mb-1 block">Sender Name</label>
                            <input type="text" name="mail_from_name"
                                   value="{{ old('mail_from_name', $bConfig['mail_from_name'] ?? $branch->name) }}"
                                   class="input" placeholder="{{ $branch->name }}">
                            <p class="text-slate-600 text-xs mt-1">Name shown in the recipient's inbox</p>
                        </div>

                        <div class="flex flex-col justify-center">
                            <label class="text-slate-400 text-xs mb-2 block">Email Status</label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative" x-data="{ enabled: {{ $isEnabled ? 'true' : 'false' }} }">
                                    <input type="hidden" name="mail_enabled" :value="enabled ? '1' : '0'">
                                    <button type="button" @click="enabled = !enabled"
                                            :class="enabled ? 'bg-green-600' : 'bg-slate-700'"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                                        <span :class="enabled ? 'translate-x-6' : 'translate-x-1'"
                                              class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                                    </button>
                                </div>
                                <span class="text-slate-300 text-sm">Enable email for this branch</span>
                            </label>
                        </div>
                    </div>

                    {{-- Actions row --}}
                    <div class="flex items-center gap-3 pt-2 border-t border-slate-800">
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Email Config
                        </button>

                        @if($hasEmail)
                        <a href="{{ route('settings.clearBranchEmail', $branch) }}"
                           onclick="return confirm('Remove email config for {{ $branch->name }}?')"
                           class="btn-danger text-xs">
                            Remove Config
                        </a>
                        @endif
                    </div>
                </form>

                {{-- Test email panel --}}
                @if($hasEmail)
                <div class="bg-slate-900/40 border-t border-slate-800 p-5">
                    <p class="text-slate-400 text-sm font-medium mb-3">Send a Test Email</p>
                    <div class="flex gap-3 items-start">
                        <div class="flex-1">
                            <input type="email" x-model="testTo" class="input"
                                   placeholder="Send test to this address...">
                        </div>
                        <button @click="sendTest()" :disabled="testing"
                                class="btn-secondary shrink-0"
                                x-text="testing ? 'Sending…' : '📧 Send Test'">
                        </button>
                    </div>

                    {{-- Test result --}}
                    <div x-show="testResult" x-cloak class="mt-3">
                        <div :class="testResult?.success
                                ? 'bg-green-500/10 border-green-500/20 text-green-400'
                                : 'bg-red-500/10 border-red-500/20 text-red-400'"
                             class="border rounded-lg px-4 py-3 text-sm flex items-start gap-2">
                            <span x-text="testResult?.success ? '✅' : '❌'" class="text-base leading-none mt-0.5"></span>
                            <span x-text="testResult?.message"></span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach

        @if($branches->isEmpty())
        <div class="card p-10 text-center text-slate-600">
            <p class="text-sm">No active branches found.</p>
            <a href="{{ route('branches.index') }}" class="text-green-500 text-xs hover:underline mt-1 inline-block">Add a branch first →</a>
        </div>
        @endif

        {{-- App Password guide --}}
        <div class="card p-5 border border-amber-500/20 bg-amber-500/5">
            <p class="text-amber-400 font-semibold text-sm mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                How to Get a Gmail App Password
            </p>
            <ol class="space-y-1.5 text-slate-400 text-sm list-decimal list-inside">
                <li>Open <a href="https://myaccount.google.com" target="_blank" class="text-blue-400 hover:underline">myaccount.google.com</a> and sign in to the Gmail account.</li>
                <li>Click <strong class="text-slate-300">Security</strong> in the left sidebar.</li>
                <li>Under "How you sign in to Google", click <strong class="text-slate-300">2-Step Verification</strong> and enable it if not already on.</li>
                <li>Search for <strong class="text-slate-300">"App passwords"</strong> at the top of the Security page.</li>
                <li>Click <strong class="text-slate-300">App passwords</strong> → type a name (e.g. "OmniPOS") → click <strong class="text-slate-300">Create</strong>.</li>
                <li>Copy the 16-character password shown and paste it into the App Password field above.</li>
            </ol>
            <p class="text-slate-600 text-xs mt-3">⚠️ The App Password is only shown once — copy it immediately and save it somewhere safe.</p>
        </div>
    </div>

    {{-- ═══ TAB: NOTIFICATIONS ══════════════════════════════════════════════ --}}
    <div x-show="tab === 'notifications'" x-cloak>
        <form method="POST" action="{{ route('settings.notifications') }}" class="space-y-5">
            @csrf

            <div class="card p-6 space-y-5">
                <h2 class="text-white font-semibold border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Email Notifications
                </h2>
                <p class="text-slate-400 text-sm -mt-1">
                    These are sent automatically to each branch's configured Gmail address.
                    Branch email must be configured and enabled for notifications to work.
                </p>

                @php
                $toggles = [
                    'notify_low_stock'     => ['label' => 'Low Stock Alerts',     'desc' => 'Send an email to the branch Gmail when any product drops to or below its stock alert level.', 'icon' => '📦'],
                    'notify_new_sale'      => ['label' => 'New Sale Notifications','desc' => 'Send an email for every completed sale. (Best for low-volume stores only.)', 'icon' => '🧾'],
                    'notify_daily_summary' => ['label' => 'Daily Sales Summary',   'desc' => 'Send a daily revenue and profit summary email to each branch at end of business.', 'icon' => '📊'],
                    'notify_debt_reminder' => ['label' => 'Debt Reminders',        'desc' => 'Send weekly email summaries listing customers with outstanding balances.', 'icon' => '💳'],
                ];
                @endphp

                <div class="space-y-4">
                    @foreach($toggles as $key => $info)
                    <div class="flex items-start justify-between gap-4 py-4 border-b border-slate-800/60 last:border-0"
                         x-data="{ enabled: {{ $notifications[$key] ? 'true' : 'false' }} }">
                        <div class="flex gap-3">
                            <span class="text-2xl leading-none mt-0.5">{{ $info['icon'] }}</span>
                            <div>
                                <p class="text-white text-sm font-medium">{{ $info['label'] }}</p>
                                <p class="text-slate-500 text-xs mt-0.5 leading-relaxed">{{ $info['desc'] }}</p>
                            </div>
                        </div>
                        <div class="shrink-0 flex flex-col items-end gap-1">
                            <input type="hidden" name="{{ $key }}" :value="enabled ? '1' : '0'">
                            <button type="button" @click="enabled = !enabled"
                                    :class="enabled ? 'bg-green-600' : 'bg-slate-700'"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                                <span :class="enabled ? 'translate-x-6' : 'translate-x-1'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                            </button>
                            <span class="text-xs" :class="enabled ? 'text-green-500' : 'text-slate-600'"
                                  x-text="enabled ? 'On' : 'Off'"></span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Branch status summary --}}
            <div class="card p-5">
                <h3 class="text-white font-medium text-sm mb-3">Branch Email Status</h3>
                <div class="space-y-2">
                    @foreach($branches as $branch)
                    @php
                        $bCfg = $branchMailConfigs[$branch->id] ?? [];
                        $hasM  = !empty($bCfg['mail_gmail_address']);
                        $active= !empty($bCfg['mail_enabled']);
                    @endphp
                    <div class="flex items-center justify-between py-2 border-b border-slate-800/50 last:border-0">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $hasM && $active ? 'bg-green-500' : ($hasM ? 'bg-amber-500' : 'bg-slate-600') }}"></div>
                            <span class="text-slate-300 text-sm">{{ $branch->name }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($hasM)
                                <span class="text-slate-500 text-xs font-mono">{{ $bCfg['mail_gmail_address'] }}</span>
                                <span class="badge text-xs {{ $active ? 'bg-green-500/10 text-green-400' : 'bg-amber-500/10 text-amber-400' }}">
                                    {{ $active ? 'Active' : 'Paused' }}
                                </span>
                            @else
                                <button type="button" @click="$dispatch('switch-tab', 'email')"
                                        onclick="document.querySelector('[\\@click=\\'tab = \\'email\\'\\']').click()"
                                        class="text-xs text-green-500 hover:underline">+ Configure email</button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary px-8">Save Notification Settings</button>
            </div>
        </form>
    </div>

</div>
@endsection