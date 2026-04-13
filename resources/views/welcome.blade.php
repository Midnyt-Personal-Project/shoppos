<!DOCTYPE html>

<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Login | OmniPOS</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-tertiary": "#ffffff",
                        "surface": "#f7f9fb",
                        "on-background": "#191c1e",
                        "on-error": "#ffffff",
                        "on-primary": "#ffffff",
                        "tertiary-container": "#007f36",
                        "on-secondary-fixed-variant": "#3a485b",
                        "tertiary": "#006329",
                        "secondary-container": "#d5e3fc",
                        "secondary": "#515f74",
                        "surface-container": "#eceef0",
                        "surface-container-lowest": "#ffffff",
                        "on-surface-variant": "#434655",
                        "surface-container-high": "#e6e8ea",
                        "primary-fixed-dim": "#b4c5ff",
                        "inverse-primary": "#b4c5ff",
                        "tertiary-fixed": "#7ffc97",
                        "surface-dim": "#d8dadc",
                        "surface-tint": "#0053db",
                        "background": "#f7f9fb",
                        "on-primary-container": "#eeefff",
                        "primary": "#004ac6",
                        "surface-container-highest": "#e0e3e5",
                        "secondary-fixed-dim": "#b9c7df",
                        "primary-fixed": "#dbe1ff",
                        "outline": "#737686",
                        "inverse-on-surface": "#eff1f3",
                        "surface-variant": "#e0e3e5",
                        "surface-container-low": "#f2f4f6",
                        "error-container": "#ffdad6",
                        "inverse-surface": "#2d3133",
                        "on-primary-fixed": "#00174b",
                        "outline-variant": "#c3c6d7",
                        "on-primary-fixed-variant": "#003ea8",
                        "on-secondary-fixed": "#0d1c2e",
                        "on-tertiary-fixed": "#002109",
                        "surface-bright": "#f7f9fb",
                        "on-surface": "#191c1e",
                        "tertiary-fixed-dim": "#62df7d",
                        "primary-container": "#2563eb",
                        "on-tertiary-container": "#c7ffca",
                        "secondary-fixed": "#d5e3fc",
                        "on-error-container": "#93000a",
                        "on-secondary-container": "#57657a",
                        "on-tertiary-fixed-variant": "#005320",
                        "on-secondary": "#ffffff",
                        "error": "#ba1a1a"
                    },
                    fontFamily: {
                        "headline": ["Inter"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    },
                    borderRadius: { "DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem" },
                },
            },
        }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">
<main class="flex-grow flex items-center justify-center p-6 sm:p-12 relative overflow-hidden">
<!-- Background decorative elements following the "Digital Concierge" North Star -->
<div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-secondary-container/30 rounded-full blur-[120px]"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-primary-container/10 rounded-full blur-[120px]"></div>
<div class="w-full max-w-md z-10">
<!-- Brand Identity Section -->
<div class="text-center mb-10">
<div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-xl mb-6 shadow-lg shadow-primary/20">
<span class="material-symbols-outlined text-on-primary text-4xl" data-icon="point_of_sale">point_of_sale</span>
</div>
<h1 class="text-3xl font-black tracking-tight text-on-background mb-2">OmniPOS</h1>
<p class="text-on-surface-variant font-medium">Secure Point of Sale Access</p>
</div>
<!-- Login Card -->
<div class="bg-surface-container-lowest rounded-full p-8 shadow-[0px_12px_32px_rgba(25,28,30,0.06)] border border-outline-variant/15">
<form action="#" class="space-y-6">
<!-- Input Groups using the "No-Line" Rule & surface-nesting -->
<div class="space-y-4">
<div class="group">
<label class="block text-sm font-semibold text-on-surface-variant mb-1.5 px-1" for="email">Email or Username</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-4 text-outline" data-icon="person">person</span>
<input class="w-full h-14 pl-12 pr-4 bg-surface-container-high border-none rounded-xl focus:ring-2 focus:ring-primary transition-all text-on-surface placeholder:text-outline/60 font-medium" id="email" name="email" placeholder="cashier_01" type="text"/>
</div>
</div>
<div class="group">
<label class="block text-sm font-semibold text-on-surface-variant mb-1.5 px-1" for="password">Password</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-4 text-outline" data-icon="lock">lock</span>
<input class="w-full h-14 pl-12 pr-12 bg-surface-container-high border-none rounded-xl focus:ring-2 focus:ring-primary transition-all text-on-surface placeholder:text-outline/60 font-medium" id="password" name="password" placeholder="••••••••" type="password"/>
<button class="absolute right-4 text-outline hover:text-primary transition-colors" type="button">
<span class="material-symbols-outlined" data-icon="visibility">visibility</span>
</button>
</div>
</div>
</div>
<div class="flex items-center justify-between px-1">
<label class="flex items-center space-x-2 cursor-pointer group">
<div class="w-5 h-5 bg-surface-container-high rounded flex items-center justify-center group-hover:bg-secondary-container transition-colors">
<input class="hidden peer" type="checkbox"/>
<span class="material-symbols-outlined text-[16px] text-primary opacity-0 peer-checked:opacity-100" data-icon="check">check</span>
</div>
<span class="text-sm font-medium text-on-surface-variant">Remember me</span>
</label>
<a class="text-sm font-bold text-primary hover:text-on-primary-fixed-variant transition-colors" href="#">Forgot Password?</a>
</div>
<!-- Primary Action: Signature Texture Gradient Button -->
<a href="/admin/dashboard" class="w-full h-14 bg-gradient-to-r from-primary to-primary-container text-on-primary font-bold rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center space-x-2" type="submit">
<span>Sign In</span>
<span class="material-symbols-outlined text-lg" data-icon="arrow_forward">arrow_forward</span>
</a>
</form>
<!-- Biometric Alternative -->
<div class="mt-8 pt-8 border-t border-outline-variant/15 text-center">
<p class="text-xs font-bold text-outline uppercase tracking-widest mb-4">Or Quick Access</p>
<button class="inline-flex items-center justify-center space-x-2 px-6 py-3 bg-secondary-container text-on-secondary-container rounded-full font-bold text-sm hover:opacity-80 active:scale-95 transition-all">
<span class="material-symbols-outlined" data-icon="fingerprint">fingerprint</span>
<span>Use Biometrics</span>
</button>
</div>
</div>
<!-- Footer Meta -->
<!-- <div class="mt-10 text-center space-y-4">
<p class="text-on-surface-variant text-sm font-medium">
                    New station? <a class="text-primary font-bold" href="#">Register Hardware</a>
</p>
<div class="flex items-center justify-center space-x-6">
<div class="flex items-center text-xs font-semibold text-outline">
<span class="material-symbols-outlined text-sm mr-1" data-icon="security">security</span>
                        Encrypted Connection
                    </div>
<div class="flex items-center text-xs font-semibold text-outline">
<span class="material-symbols-outlined text-sm mr-1" data-icon="cloud_done">cloud_done</span>
                        v4.2.0 Stable
                    </div>
</div> -->
</div>
</div>
</main>
<!-- Support Floating Action - Only for Help -->
<div class="fixed bottom-6 right-6 z-50">
<button class="w-14 h-14 bg-surface-container-lowest text-primary rounded-full shadow-xl flex items-center justify-center hover:bg-primary hover:text-on-primary transition-all duration-300 group border border-outline-variant/10">
<span class="material-symbols-outlined" data-icon="help_outline">help_outline</span>
<span class="max-w-0 overflow-hidden group-hover:max-w-xs group-hover:ml-2 transition-all duration-300 font-bold text-sm whitespace-nowrap">Support</span>
</button>
</div>
</body></html>