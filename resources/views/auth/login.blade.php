<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#16a34a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">
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
        [x-cloak] { display: none !important; }

        /* Hero background with African shopkeeper overlay */
        .hero-bg {
            position: relative;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                /* Dark overlay for readability */
                linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.85) 100%),
                /* Background image - using a free stock photo of an African shopkeeper */
                url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MDAiIGhlaWdodD0iNjAwIiB2aWV3Qm94PSIwIDAgODAwIDYwMCI+CiAgPGRlZnM+CiAgICA8cmFkaWFsR3JhZGllbnQgaWQ9ImciIGN4PSI1MCUiIGN5PSI1MCUiIHI9IjcwJSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMxZTNiMmI7c3RvcC1vcGFjaXR5OjEiIC8+CiAgICAgIDxzdG9wIG9mZnNldD0iMTAwJSIgc3R5bGU9InN0b3AtY29sb3I6IzBmMTcyYTtzdG9wLW9wYWNpdHk6MSIgLz4KICAgIDwvcmFkaWFsR3JhZGllbnQ+CiAgPC9kZWZzPgogIDxyZWN0IHdpZHRoPSI4MDAiIGhlaWdodD0iNjAwIiBmaWxsPSJ1cmwoI2cpIiAvPgogIAogIDwhLS0gU2hvcGtlZXBlciBzaWxob3VldHRlIC0tPgogIDxjaXJjbGUgY3g9IjUwMCIgY3k9IjI4MCIgcj0iMTUwIiBmaWxsPSIjMTk0YzNhIiBvcGFjaXR5PSIwLjYiIC8+CiAgPCEtLSBIZWFkIC0tPgogIDxjaXJjbGUgY3g9IjUwMCIgY3k9IjIyMCIgcj0iNDAiIGZpbGw9IiM4YjZkNDUiIG9wYWNpdHk9IjAuNyIgLz4KICA8IS0tIEJvZHkgLS0+CiAgPHJlY3QgeD0iNDUwIiB5PSIyNjAiIHdpZHRoPSIxMDAiIGhlaWdodD0iMTUwIiBmaWxsPSIjMTk0YzNhIiBvcGFjaXR5PSIwLjYiIC8+CiAgPCEtLSBBcm1zIC0tPgogIDxjaXJjbGUgY3g9IjQ2MCIgY3k9IjI4MCIgcj0iMTUiIGZpbGw9IiM4YjZkNDUiIG9wYWNpdHk9IjAuNyIgLz4KICA8Y2lyY2xlIGN4PSI1NDAiIGN5PSIyODAiIHI9IjE1IiBmaWxsPSIjOGI2ZDQ1IiBvcGFjaXR5PSIwLjciIC8+CiAgPCEtLSBMZWdzIC0tPgogIDxyZWN0IHg9IjQ2MCIgeT0iNDEwIiB3aWR0aD0iMjAiIGhlaWdodD0iNzAiIGZpbGw9IiMxOTRjM2EiIG9wYWNpdHk9IjAuNiIgLz4KICA8cmVjdCB4PSI1MjAiIHk9IjQxMCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjcwIiBmaWxsPSIjMTk0YzNhIiBvcGFjaXR5PSIwLjYiIC8+CiAgPCEtLSBBcHJvbiAtLT4KICA8cmVjdCB4PSI0NzAiIHk9IjI4MCIgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjMjA1YzQwIiBvcGFjaXR5PSIwLjUiIC8+CiAgPCEtLSBTaG9wIHNoZWxmIC0tPgogIDxyZWN0IHg9IjU4MCIgeT0iMjUwIiB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE4MCIgZmlsbD0iIzhjNjUzMCIgb3BhY2l0eT0iMC40IiAvPgogIDxyZWN0IHg9IjU5MCIgeT0iMjYwIiB3aWR0aD0iMTMwIiBoZWlnaHQ9IjE2MCIgZmlsbD0iIzdhNTgyYSIgb3BhY2l0eT0iMC4zIiAvPgogIAogIDwhLS0gUHJvZHVjdHMgLS0+CiAgPHJlY3QgeD0iNjAwIiB5PSIyNzAiIHdpZHRoPSI0MCIgaGVpZ2h0PSI1MCIgZmlsbD0iIzljNzgzNSIgb3BhY2l0eT0iMC41IiAvPgogIDxyZWN0IHg9IjY1MCIgeT0iMjcwIiB3aWR0aD0iNDAiIGhlaWdodD0iNTAiIGZpbGw9IiM5Yzc4MzUiIG9wYWNpdHk9IjAuNSIgLz4KICA8cmVjdCB4PSI2MDAiIHk9IjMzMCIgd2lkdGg9IjQwIiBoZWlnaHQ9IjUwIiBmaWxsPSIjOWM3ODM1IiBvcGFjaXR5PSIwLjUiIC8+CiAgPHJlY3QgeD0iNjUwIiB5PSIzMzAiIHdpZHRoPSI0MCIgaGVpZ2h0PSI1MCIgZmlsbD0iIzljNzgzNSIgb3BhY2l0eT0iMC41IiAvPgogIAogIDwhLS0gQ3VzdG9tZXIgLS0+CiAgPGNpcmNsZSBjeD0iMjUwIiBjeT0iNDAwIiByPSIzMCIgZmlsbD0iIzhjNjUzMCIgb3BhY2l0eT0iMC41IiAvPgogIDxyZWN0IHg9IjIyMCIgeT0iNDMwIiB3aWR0aD0iNjAiIGhlaWdodD0iNzAiIGZpbGw9IiM4YzY1MzAiIG9wYWNpdHk9IjAuNSIgLz4KICAKICA8IS0tIFRleHQgb3ZlcmxheSAtLT4KICA8dGV4dCB4PSI0MDAiIHk9IjUwMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI0IiBmaWxsPSIjMTZhMzRhIiBvcGFjaXR5PSIwLjMiPkVtcG93ZXJpbmcgQWZyaWNhbiBCdXNpbmVzczwvdGV4dD4KPC9zdmc+');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.3;
            z-index: 0;
        }

        .hero-bg > * {
            position: relative;
            z-index: 1;
        }

        /* Decorative elements */
        .hero-bg::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40%;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, transparent 100%);
            z-index: 0;
        }

        /* Card glow effect */
        .login-card {
            position: relative;
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(51, 65, 85, 0.5);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .login-card::before {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 1.25rem;
            padding: 1px;
            background: linear-gradient(135deg, rgba(22, 163, 74, 0.3), transparent, rgba(22, 163, 74, 0.1));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        /* African pattern decoration */
        .pattern-decoration {
            position: absolute;
            width: 100%;
            height: 4px;
            bottom: -2px;
            left: 0;
            background: repeating-linear-gradient(
                90deg,
                #16a34a 0px,
                #16a34a 20px,
                #fbbf24 20px,
                #fbbf24 40px,
                #16a34a 40px,
                #16a34a 60px
            );
            opacity: 0.3;
        }

        .login-card {
            position: relative;
            overflow: hidden;
        }

        .login-card .pattern-decoration {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #16a34a, #fbbf24, #16a34a, #fbbf24, #16a34a);
            background-size: 100px 100%;
            opacity: 0.4;
        }

        /* Floating African pattern */
        .african-pattern {
            position: absolute;
            opacity: 0.05;
            font-size: 120px;
            pointer-events: none;
            user-select: none;
        }

        /* Responsive background */
        @media (max-width: 768px) {
            .hero-bg::before {
                background-size: cover;
                background-position: 60% center;
            }
        }

        /* Animated gradient border */
        @keyframes borderGlow {
            0%, 100% { border-color: rgba(22, 163, 74, 0.3); }
            50% { border-color: rgba(22, 163, 74, 0.6); }
        }

        .login-card {
            animation: borderGlow 3s ease-in-out infinite;
        }

        /* Fix for button text - ensure it's always visible */
        .btn-text {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
        
        .btn-text.hidden {
            display: none !important;
        }
    </style>
</head>
<body class="h-full hero-bg">

    <div class="w-full max-w-sm px-6 relative" x-data="setupApp()" x-cloak>

        {{-- African decorative pattern --}}
        <div class="african-pattern" style="top: -60px; right: -40px; transform: rotate(15deg);">
            ✤
        </div>
        <div class="african-pattern" style="bottom: -40px; left: -30px; transform: rotate(-10deg);">
            ✥
        </div>

        {{-- Login Card --}}
        <div class="login-card rounded-2xl p-8 relative">

            {{-- Pattern decoration at bottom --}}
            <div class="pattern-decoration"></div>

            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 relative"
                     style="background:linear-gradient(135deg, #16a34a, #15803d);box-shadow:0 8px 32px rgba(22,163,74,.35)">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9M9 21h6"/>
                    </svg>
                    {{-- Small African accent --}}
                    <span style="position:absolute;bottom:-4px;right:-4px;font-size:14px;">⚡</span>
                </div>
                <h1 class="text-white text-2xl font-bold tracking-tight">OmniPOS</h1>
                <p class="text-green-400 text-sm mt-1 font-medium">Empowering African Businesses</p>
                <p class="text-slate-500 text-xs mt-0.5">Sign in to your account</p>
            </div>

            {{-- Login form --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-4" @submit="loginLoading = true">

                @csrf

                @if($errors->any())
                <div class="rounded-xl px-4 py-3" style="background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3)">
                    <p class="text-red-400 text-sm flex items-center gap-2">
                        <span>⚠️</span>
                        {{ $errors->first() }}
                    </p>
                </div>
                @endif

                <div>
                    <label class="text-slate-400 text-xs font-medium mb-1.5 block flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                        Email address
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="input py-3"
                           placeholder="you@yourshop.com"
                           style="background:rgba(15,23,42,0.6);border-color:rgba(51,65,85,0.5);">
                </div>

                <div>
                    <label class="text-slate-400 text-xs font-medium mb-1.5 block flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Password
                    </label>
                    <input type="password" name="password" required
                           class="input py-3"
                           placeholder="••••••••"
                           style="background:rgba(15,23,42,0.6);border-color:rgba(51,65,85,0.5);">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember"
                               class="rounded border-slate-700 text-green-600 focus:ring-green-600 focus:ring-offset-0"
                               style="background:rgba(15,23,42,0.6);">
                        <span class="text-slate-400 text-xs group-hover:text-slate-300 transition-colors">Remember me</span>
                    </label>
                    <a href="#" class="text-green-400 hover:text-green-300 text-xs transition-colors">
                        Forgot password?
                    </a>
                </div>

                <button type="submit"
                        :disabled="loginLoading"
                        class="w-full text-white font-semibold py-3.5 rounded-xl text-sm transition-all flex items-center justify-center gap-2 relative overflow-hidden group"
                        style="background:linear-gradient(135deg, #16a34a, #15803d);box-shadow:0 4px 16px rgba(22,163,74,.25)"
                        :class="{ 'opacity-70 cursor-not-allowed': loginLoading }">

                    {{-- Hover effect --}}
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-10 transition-opacity duration-500 -translate-x-full group-hover:translate-x-full"></span>

                    {{-- Loading spinner --}}
                    <svg x-show="loginLoading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    {{-- Button text - ALWAYS VISIBLE unless loading --}}
                    <span x-show="!loginLoading" class="btn-text">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Sign In
                    </span>

                    {{-- Loading text --}}
                    {{-- <span x-show="loginLoading" x-cloak class="btn-text">Signing in...</span> --}}
                </button>
            </form>

            {{-- Install as a web app --}}
            <div id="installAppArea" class="mt-6 text-center hidden">
                <button id="installAppButton" type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all hover:scale-105"
                        style="background:rgba(22,163,74,.14);color:#86efac;border:1px solid rgba(22,163,74,.35)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14a2 2 0 002-2v-3M5 15v3a2 2 0 002 2"/>
                    </svg>
                    Install OmniPOS App
                </button>
                <p class="text-slate-500 text-xs mt-2">Install for faster access from your device.</p>
            </div>
            <div id="iosInstallHint" class="mt-4 text-center hidden">
                <p class="text-slate-400 text-xs leading-relaxed">To install on iPhone or iPad, tap <strong class="text-slate-200">Share</strong> in Safari, then choose <strong class="text-slate-200">Add to Home Screen</strong>.</p>
            </div>

            {{-- Setup button --}}
            <div x-show="needsSetup" class="mt-6 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full mb-3"
                     style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2)">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                    <span class="text-blue-400 text-xs">First time? No shop registered yet</span>
                </div>
                <br>
                <button @click="showSetup = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all hover:scale-105"
                        style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    Set Up Your Shop
                </button>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6">
            <p class="text-slate-600 text-xs">
                © {{ date('Y') }} OmniPOS — Built for African entrepreneurs 🇬🇭🇳🇬🇰🇪🇿🇦
            </p>
            <div class="flex items-center justify-center gap-2 mt-2">
                <span style="font-size:12px;">🤝</span>
                <span class="text-slate-700 text-xs">Supporting local businesses</span>
                <span style="font-size:12px;">🌍</span>
            </div>
        </div>

        {{-- Setup Modal --}}
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

                    <div x-show="errorMsg" x-cloak
                         class="mb-4 flex items-start gap-2.5 px-4 py-3 rounded-xl text-sm"
                         style="background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.25);color:#f87171">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                        </svg>
                        <span x-text="errorMsg"></span>
                    </div>

                    {{-- Tab 1: Shop Info --}}
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
                                <input type="tel" x-model="form.shop_phone" class="input py-2.5" placeholder="0244000000">
                            </div>
                            <div>
                                <label class="text-slate-400 text-xs font-medium mb-1.5 block">Business Email</label>
                                <input type="email" x-model="form.shop_email" class="input py-2.5" placeholder="info@shop.com">
                            </div>
                        </div>

                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">Address</label>
                            <input type="text" x-model="form.shop_address" class="input py-2.5" placeholder="e.g. Accra Central, Ghana">
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
                                style="background:#16a34a">
                            Next: Admin Account →
                        </button>
                    </div>

                    {{-- Tab 2: Admin Account --}}
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
                            <input type="text" x-model="form.name" required class="input py-2.5" placeholder="e.g. Kwame Mensah">
                            <p x-show="errors.name" x-text="errors.name" class="text-red-400 text-xs mt-1"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                                    Email Address <span class="text-red-400">*</span>
                                </label>
                                <input type="email" x-model="form.email" required class="input py-2.5" placeholder="owner@shop.com">
                                <p x-show="errors.email" x-text="errors.email" class="text-red-400 text-xs mt-1"></p>
                            </div>
                            <div>
                                <label class="text-slate-400 text-xs font-medium mb-1.5 block">Phone Number</label>
                                <input type="tel" x-model="form.phone" class="input py-2.5" placeholder="0244000000">
                            </div>
                        </div>

                        <div>
                            <label class="text-slate-400 text-xs font-medium mb-1.5 block">
                                Password <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
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
                                    class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all">
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
    </div>

    <script>
        // Browser-controlled install prompt for the OmniPOS Progressive Web App.
        let deferredInstallPrompt;
        const installArea = document.getElementById('installAppArea');
        const installButton = document.getElementById('installAppButton');
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredInstallPrompt = event;
            installArea?.classList.remove('hidden');
        });
        installButton?.addEventListener('click', async () => {
            if (!deferredInstallPrompt) return;
            deferredInstallPrompt.prompt();
            await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;
            installArea?.classList.add('hidden');
        });
        window.addEventListener('appinstalled', () => installArea?.classList.add('hidden'));
        const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        if (isIos && !isStandalone) document.getElementById('iosInstallHint')?.classList.remove('hidden');

        function setupApp() {
            return {
                // State
                needsSetup: false,
                showSetup: false,
                tab: 'shop',
                submitting: false,
                showPass: false,
                errorMsg: '',
                errors: {},
                loginLoading: false, // Added this

                form: {
                    shop_name: '',
                    shop_phone: '',
                    shop_email: '',
                    shop_address: '',
                    currency: 'GHS',
                    currency_symbol: '₵',
                    name: '',
                    email: '',
                    phone: '',
                    password: '',
                    password_confirmation: '',
                },

                // Computed
                get passwordStrength() {
                    const p = this.form.password;
                    if (!p) return 0;
                    let score = 0;
                    if (p.length >= 8) score++;
                    if (p.length >= 12) score++;
                    if (/[A-Z]/.test(p)) score++;
                    if (/[0-9!@#$%^&*]/.test(p)) score++;
                    return Math.min(score, 4);
                },

                // Lifecycle
                async init() {
                    console.log('Alpine component initialised');
                    try {
                        const res = await fetch('{{ route("setup.check") }}');
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        console.log('Setup check response:', data);
                        this.needsSetup = data.needs_setup === true;
                        console.log('needsSetup set to:', this.needsSetup);
                    } catch (e) {
                        console.error('Setup check failed:', e);
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
                    this.errors = {};
                    this.errorMsg = '';

                    if (!this.form.name.trim()) {
                        this.errors.name = 'Full name is required.';
                        return;
                    }
                    if (!this.form.email.trim()) {
                        this.errors.email = 'Email is required.';
                        return;
                    }
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
                        const res = await fetch('{{ route("setup.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(this.form),
                        });

                        const data = await res.json();

                        if (data.success) {
                            window.location.href = data.redirect;
                        } else if (res.status === 422) {
                            const errs = data.errors || {};
                            this.errors = {};

                            if (errs.shop_name) {
                                this.errors.shop_name = errs.shop_name[0];
                                this.tab = 'shop';
                            }
                            if (errs.currency) {
                                this.errors.currency = errs.currency[0];
                                this.tab = 'shop';
                            }
                            if (errs.name) this.errors.name = errs.name[0];
                            if (errs.email) this.errors.email = errs.email[0];
                            if (errs.password) this.errors.password = errs.password[0];

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