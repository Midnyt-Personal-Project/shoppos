<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — OmniPOS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .grid-bg {
            background-image:
                linear-gradient(rgba(22,163,74,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(22,163,74,.04) 1px, transparent 1px);
            background-size: 44px 44px;
        }
        .modal-enter { animation: modalIn .25s cubic-bezier(.16,1,.3,1) both; }
        @keyframes modalIn {
            from { opacity:0; transform: scale(.96) translateY(12px); }
            to   { opacity:1; transform: scale(1)  translateY(0); }
        }
    </style>
</head>
<body class="h-full flex items-center justify-center grid-bg"
      style="background-color:#0f172a"
      x-data="setupApp()">

    {{-- ── Login Card ──────────────────────────────────────────────────────── --}}
    <div class="w-full max-w-sm px-6">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background:#16a34a;box-shadow:0 8px 32px rgba(22,163,74,.35)">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21h6"/>
                </svg>
            </div>
            <h1 class="text-white text-2xl font-bold">OmniPOS</h1>
            <p class="text-slate-400 text-sm mt-1">Sign in to your account</p>
        </div>

        {{-- Login form --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            @if($errors->any())
            <div class="rounded-xl px-4 py-3" style="background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3)">
                <p class="text-red-400 text-sm">{{ $errors->first() }}</p>
            </div>
            @endif

            <div>
                <label class="text-slate-400 text-xs font-medium mb-1.5 block">Email address</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="input py-3"
                       placeholder="you@yourshop.com">
            </div>

            <div>
                <label class="text-slate-400 text-xs font-medium mb-1.5 block">Password</label>
                <input type="password" name="password" required
                       class="input py-3"
                       placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember"
                           class="rounded border-slate-700 text-green-600 focus:ring-green-600"
                           style="background:#0f172a">
                    <span class="text-slate-400 text-xs">Remember me</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full text-white font-semibold py-3 rounded-xl text-sm transition-all"
                    style="background:#16a34a;box-shadow:0 4px 16px rgba(22,163,74,.25)"
                    onmouseover="this.style.background='#22c55e'"
                    onmouseout="this.style.background='#16a34a'">
                Sign In
            </button>
        </form>

        {{-- Setup button — only shown when DB is empty --}}
        <div x-show="needsSetup" x-cloak class="mt-6 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full mb-3"
                 style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2)">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                <span class="text-blue-400 text-xs">First time? No shop registered yet</span>
            </div>
            <br>
            <button @click="showSetup = true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all"
                    style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)"
                    onmouseover="this.style.background='rgba(59,130,246,.25)'"
                    onmouseout="this.style.background='rgba(59,130,246,.15)'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Set Up Your Shop
            </button>
        </div>

    </div>

    {{-- ── Setup Modal ─────────────────────────────────────────────────────── --}}
    <div x-show="showSetup" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">

        <div class="w-full max-w-lg modal-enter"
             style="background:#1e293b;border:1px solid #334155;border-radius:1.25rem;overflow:hidden;max-height:90vh;display:flex;flex-direction:column"
             @click.outside="showSetup = false">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-5"
                 style="border-bottom:1px solid #334155;background:linear-gradient(135deg,#14532d,#15803d)">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:rgba(255,255,255,.15)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-bold text-base">First-Time Setup</p>
                        <p class="text-green-200 text-xs">Create your shop and admin account</p>
                    </div>
                </div>
                <button @click="showSetup = false"
                        class="text-green-200 hover:text-white transition-colors p-1 bg-transparent border-0 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Step tabs --}}
            <div class="flex" style="border-bottom:1px solid #334155">
                <button @click="tab = 'shop'"
                        :style="tab === 'shop'
                            ? 'border-bottom:2px solid #16a34a;color:#fff;background:rgba(22,163,74,.08)'
                            : 'color:#64748b;border-bottom:2px solid transparent'"
                        class="flex-1 py-3.5 text-sm font-medium transition-all flex items-center justify-center gap-2 bg-transparent border-0 cursor-pointer">
                    <span :class="tab === 'shop' ? 'bg-green-600 text-white' : 'bg-slate-700 text-slate-400'"
                          class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center transition-all">1</span>
                    Shop Info
                </button>
                <button @click="tab = 'user'"
                        :style="tab === 'user'
                            ? 'border-bottom:2px solid #16a34a;color:#fff;background:rgba(22,163,74,.08)'
                            : 'color:#64748b;border-bottom:2px solid transparent'"
                        class="flex-1 py-3.5 text-sm font-medium transition-all flex items-center justify-center gap-2 bg-transparent border-0 cursor-pointer">
                    <span :class="tab === 'user' ? 'bg-green-600 text-white' : 'bg-slate-700 text-slate-400'"
                          class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center transition-all">2</span>
                    Admin Account
                </button>
            </div>

            {{-- Scrollable form body --}}
            <div class="overflow-y-auto flex-1 px-6 py-5">

                {{-- Error banner --}}
                <div x-show="errorMsg" x-cloak
                     class="mb-4 flex items-start gap-2.5 px-4 py-3 rounded-xl text-sm"
                     style="background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.25);color:#f87171">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <span x-text="errorMsg"></span>
                </div>

                {{-- ── TAB 1: Shop Info ───────────────────────────────────── --}}
                <div x-show="tab === 'shop'" class="space-y-4">

                    <div class="flex items-start gap-3 p-3 rounded-xl"
                         style="background:rgba(22,163,74,.06);border:1px solid rgba(22,163,74,.15)">
                        <svg class="w-4 h-4 text-green-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-green-300 text-xs leading-relaxed">
                            A <strong>Main Branch</strong> will be created automatically using this shop info.
                            You can add more branches later from the Admin panel.
                        </p>
                    </div>

                    <div>
                        <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                            Shop / Business Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" x-model="form.shop_name" required
                               class="input py-2.5"
                               placeholder="e.g. Mensah's Supermarket">
                        <p x-show="errors.shop_name" x-text="errors.shop_name" class="text-red-400 text-xs mt-1"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">Phone Number</label>
                            <input type="tel" x-model="form.shop_phone"
                                   class="input py-2.5" placeholder="0244000000">
                        </div>
                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">Business Email</label>
                            <input type="email" x-model="form.shop_email"
                                   class="input py-2.5" placeholder="info@shop.com">
                        </div>
                    </div>

                    <div>
                        <label class="text-slate-400 text-xs font-medium mb-1.5 block">Address</label>
                        <input type="text" x-model="form.shop_address"
                               class="input py-2.5" placeholder="e.g. Accra Central, Ghana">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                                Currency Code <span class="text-red-400">*</span>
                            </label>
                            <input type="text" x-model="form.currency" required maxlength="10"
                                   class="input py-2.5 font-mono" placeholder="GHS">
                            <p class="text-slate-600 text-xs mt-1">GHS, USD, NGN, KES…</p>
                        </div>
                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                                Currency Symbol <span class="text-red-400">*</span>
                            </label>
                            <input type="text" x-model="form.currency_symbol" required maxlength="5"
                                   class="input py-2.5 font-mono text-xl text-center" placeholder="₵">
                            <p class="text-slate-600 text-xs mt-1">₵, $, ₦, KSh…</p>
                        </div>
                    </div>

                    <button type="button" @click="goToUser()"
                            class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all mt-2"
                            style="background:#16a34a"
                            onmouseover="this.style.background='#22c55e'"
                            onmouseout="this.style.background='#16a34a'">
                        Next: Admin Account →
                    </button>
                </div>

                {{-- ── TAB 2: Admin Account ───────────────────────────────── --}}
                <div x-show="tab === 'user'" class="space-y-4">

                    <div class="flex items-start gap-3 p-3 rounded-xl"
                         style="background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15)">
                        <svg class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <p class="text-blue-300 text-xs leading-relaxed">
                            This account will be the <strong>Shop Owner</strong> with full access to everything.
                            Use a strong password — you can add more staff later.
                        </p>
                    </div>

                    <div>
                        <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                            Full Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" x-model="form.name" required
                               class="input py-2.5" placeholder="e.g. Kwame Mensah">
                        <p x-show="errors.name" x-text="errors.name" class="text-red-400 text-xs mt-1"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                                Email Address <span class="text-red-400">*</span>
                            </label>
                            <input type="email" x-model="form.email" required
                                   class="input py-2.5" placeholder="owner@shop.com">
                            <p x-show="errors.email" x-text="errors.email" class="text-red-400 text-xs mt-1"></p>
                        </div>
                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">Phone Number</label>
                            <input type="tel" x-model="form.phone"
                                   class="input py-2.5" placeholder="0244000000">
                        </div>
                    </div>

                    <div>
                        <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                            Password <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            :type="showPass ? 'text' : 'password'"
                            <input :type="showPass ? 'text' : 'password'"
                                   x-model="form.password" required minlength="8"
                                   class="input py-2.5 pr-10" placeholder="Minimum 8 characters">
                            <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors bg-transparent border-0 cursor-pointer p-0">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <p x-show="errors.password" x-text="errors.password" class="text-red-400 text-xs mt-1"></p>

                        {{-- Password strength indicator --}}
                        <div class="flex gap-1 mt-2" x-show="form.password.length > 0">
                            <template x-for="i in 4">
                                <div class="h-1 flex-1 rounded-full transition-all"
                                     :style="passwordStrength >= i
                                         ? (passwordStrength >= 4 ? 'background:#16a34a' : passwordStrength >= 3 ? 'background:#22c55e' : passwordStrength >= 2 ? 'background:#f59e0b' : 'background:#ef4444')
                                         : 'background:#334155'">
                                </div>
                            </template>
                            <span class="text-xs ml-1 w-16"
                                  :style="passwordStrength >= 4 ? 'color:#16a34a' : passwordStrength >= 3 ? 'color:#22c55e' : passwordStrength >= 2 ? 'color:#f59e0b' : 'color:#ef4444'"
                                  x-text="['','Weak','Fair','Good','Strong'][passwordStrength]">
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                            Confirm Password <span class="text-red-400">*</span>
                        </label>
                        <input type="password" x-model="form.password_confirmation" required
                               class="input py-2.5" placeholder="Re-enter password"
                               :style="form.password_confirmation && form.password !== form.password_confirmation ? 'border-color:#ef4444' : ''">
                        <p x-show="form.password_confirmation && form.password !== form.password_confirmation"
                           class="text-red-400 text-xs mt-1">Passwords do not match</p>
                    </div>

                    {{-- Summary of what will be created --}}
                    <div x-show="form.shop_name" class="rounded-xl p-3.5 space-y-1.5"
                         style="background:rgba(15,23,42,.6);border:1px solid #334155">
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-wider mb-2">Will be created:</p>
                        <div class="flex items-center gap-2 text-xs text-slate-300">
                            <span class="text-green-400">✓</span>
                            Shop: <strong x-text="form.shop_name || '—'" class="text-white"></strong>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-300">
                            <span class="text-green-400">✓</span>
                            Branch: <strong class="text-white">Main Branch</strong>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-300">
                            <span class="text-green-400">✓</span>
                            Owner: <strong x-text="form.name || '—'" class="text-white"></strong>
                            (<span x-text="form.email || '—'"></span>)
                        </div>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="tab = 'shop'"
                                class="btn-secondary flex-1 justify-center py-2.5">
                            ← Back
                        </button>
                        <button type="button" @click="submit()"
                                :disabled="submitting || form.password !== form.password_confirmation"
                                :style="submitting || form.password !== form.password_confirmation
                                    ? 'opacity:.5;cursor:not-allowed;background:#16a34a'
                                    : 'background:#16a34a'"
                                class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                                onmouseover="if(!this.disabled) this.style.background='#22c55e'"
                                onmouseout="this.style.background='#16a34a'">
                            <span x-show="!submitting" class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Create Shop & Login
                            </span>
                            <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Setting up…
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

<script>
function setupApp() {
    return {
        needsSetup: false,
        showSetup:  false,
        tab:        'shop',
        submitting: false,
        showPass:   false,
        errorMsg:   '',
        errors:     {},

        form: {
            shop_name:            '',
            shop_phone:           '',
            shop_email:           '',
            shop_address:         '',
            currency:             'GHS',
            currency_symbol:      '₵',
            name:                 '',
            email:                '',
            phone:                '',
            password:             '',
            password_confirmation:'',
        },

        get passwordStrength() {
            const p = this.form.password;
            if (!p) return 0;
            let score = 0;
            if (p.length >= 8)              score++;
            if (p.length >= 12)             score++;
            if (/[A-Z]/.test(p))            score++;
            if (/[0-9!@#$%^&*]/.test(p))    score++;
            return score;
        },

        async init() {
            // Check if setup is needed by calling the API
            try {
                const res  = await fetch('/setup/check');
                const data = await res.json();
                this.needsSetup = data.needs_setup;
            } catch (e) {
                // If the endpoint fails, assume not needed
                this.needsSetup = false;
            }
        },

        goToUser() {
            this.errors = {};
            if (!this.form.shop_name.trim()) {
                this.errors.shop_name = 'Shop name is required.';
                return;
            }
            if (!this.form.currency.trim()) {
                this.errors.currency = 'Currency code is required.';
                return;
            }
            if (!this.form.currency_symbol.trim()) {
                this.errors.currency_symbol = 'Currency symbol is required.';
                return;
            }
            this.tab = 'user';
        },

        async submit() {
            this.errors   = {};
            this.errorMsg = '';

            // Client-side validation
            if (!this.form.name.trim())  { this.errors.name  = 'Full name is required.';  return; }
            if (!this.form.email.trim()) { this.errors.email = 'Email is required.';       return; }
            if (this.form.password.length < 8) {
                this.errors.password = 'Password must be at least 8 characters.';
                return;
            }
            if (this.form.password !== this.form.password_confirmation) {
                this.errorMsg = 'Passwords do not match.';
                return;
            }

            this.submitting = true;

            try {
                const res = await fetch('/setup', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();

                if (data.success) {
                    // Redirect to dashboard
                    window.location.href = data.redirect;
                } else if (res.status === 422) {
                    // Laravel validation errors
                    const errs = data.errors || {};
                    this.errors   = {};
                    this.errorMsg = '';

                    // Map Laravel field names to form fields
                    if (errs.shop_name)       { this.errors.shop_name = errs.shop_name[0]; this.tab = 'shop'; }
                    if (errs.currency)        { this.errors.currency  = errs.currency[0];  this.tab = 'shop'; }
                    if (errs.name)            { this.errors.name      = errs.name[0]; }
                    if (errs.email)           { this.errors.email     = errs.email[0]; }
                    if (errs.password)        { this.errors.password  = errs.password[0]; }
                    if (!Object.keys(this.errors).length) {
                        this.errorMsg = data.message || 'Validation failed.';
                    }
                } else {
                    this.errorMsg = data.message || 'Something went wrong. Please try again.';
                }
            } catch (e) {
                this.errorMsg = 'Network error. Please check your connection.';
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>

</body>
</html>