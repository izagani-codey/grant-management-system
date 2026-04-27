@php
    $appName = $settings['app_name']->value ?? config('app.name', 'Grant Request System');
    $institutionName = $settings['institution_name']->value ?? config('system.branding.organization', 'Your Organization');
    $tagline = $settings['institution_tagline']->value ?? 'Workflow and Approval Management';
    $primaryColor = $settings['primary_color']->value ?? '#003087';
    $accentColor = $settings['accent_color']->value ?? '#C8971E';
    $footerText = $settings['footer_text']->value ?? '';
    $logoPath = $settings['app_logo']->value ?? '';
    $logoUrl = $logoPath ? asset('storage/' . $logoPath) : null;
    $brandInitial = mb_strtoupper(mb_substr($institutionName ?: $appName, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --primary-color: {{ $primaryColor }};
                --accent-color: {{ $accentColor }};
            }
            .brand-panel {
                background: linear-gradient(160deg, var(--primary-color) 0%, #1a4fa0 100%);
            }
            .top-bar {
                height: 4px;
                background: linear-gradient(90deg, var(--primary-color) 70%, var(--accent-color) 100%);
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased min-h-screen bg-gray-100">

        <!-- Top accent bar -->
        <div class="top-bar fixed top-0 left-0 right-0 z-50"></div>

        <div class="min-h-screen flex flex-col md:flex-row pt-1">

            {{-- Left branding panel — hidden on small screens, shown on md+ --}}
            <div class="hidden md:flex brand-panel md:w-2/5 lg:w-1/3 flex-col items-center justify-center px-10 py-16 text-white text-center">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $institutionName }} Logo" class="w-28 h-28 object-contain mb-6 drop-shadow-lg">
                @else
                    <div class="w-28 h-28 rounded-2xl text-white font-bold text-4xl flex items-center justify-center mb-6 border border-white/20" style="background: rgba(255,255,255,0.15);">
                        {{ $brandInitial }}
                    </div>
                @endif
                <h1 class="text-2xl font-extrabold tracking-wide mb-1">{{ $institutionName }}</h1>
                <p class="text-sm font-semibold mb-6" style="color: var(--accent-color);">{{ $tagline }}</p>
                <div class="border-t border-white/20 pt-6 w-full">
                    <p class="text-base font-bold mb-1">{{ $appName }}</p>
                    <p class="text-xs text-blue-200 leading-relaxed">
                        Request Workflow<br>
                        Review &amp; Approval Management
                    </p>
                </div>
                <div class="mt-auto pt-10 text-xs text-blue-200/60">
                    {{ $footerText ?: '© ' . date('Y') . ' ' . $institutionName }}
                </div>
            </div>

            {{-- Right form panel --}}
            <div class="flex-1 flex flex-col">

                {{-- Mobile-only header --}}
                <header class="md:hidden bg-white border-b px-4 py-3 flex items-center gap-3" style="border-bottom: 2px solid var(--primary-color);">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $institutionName }}" class="h-9 w-auto">
                    @else
                        <div class="h-9 w-9 rounded-lg text-white font-bold flex items-center justify-center" style="background: var(--primary-color);">
                            {{ $brandInitial }}
                        </div>
                    @endif
                    <div class="leading-tight">
                        <p class="text-sm font-extrabold" style="color: var(--primary-color);">{{ $institutionName }}</p>
                        <p class="text-xs text-gray-500">{{ $appName }}</p>
                    </div>
                    <nav class="ml-auto flex gap-2 text-sm font-medium">
                        <a href="{{ url('/') }}" class="px-3 py-1.5 rounded text-gray-600 hover:bg-gray-100 text-xs">Home</a>
                        @guest
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded text-xs font-semibold" style="color: var(--primary-color);">Login</a>
                        @endguest
                    </nav>
                </header>

                {{-- Desktop top nav --}}
                <div class="hidden md:flex items-center justify-end px-8 py-4 bg-white border-b border-gray-200">
                    <nav class="flex items-center gap-3 text-sm font-medium">
                        <a href="{{ url('/') }}" class="px-3 py-1.5 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-100">← Home</a>
                        @guest
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded font-semibold" style="color: var(--primary-color);">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-1.5 rounded-lg text-white font-semibold text-sm"
                                   style="background: var(--primary-color);">
                                    Register
                                </a>
                            @endif
                        @endguest
                    </nav>
                </div>

                {{-- Form slot --}}
                <main class="flex-1 flex items-start md:items-center justify-center px-4 py-10 sm:px-8 overflow-y-auto">
                    <div class="w-full max-w-lg">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
