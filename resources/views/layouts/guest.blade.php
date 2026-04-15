<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'UniKL MIIT') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --miit-blue: #003087;
                --miit-gold: #C8971E;
            }
            .miit-panel {
                background: linear-gradient(160deg, #003087 0%, #1a4fa0 100%);
            }
            .top-bar {
                height: 4px;
                background: linear-gradient(90deg, var(--miit-blue) 70%, var(--miit-gold) 100%);
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased min-h-screen bg-gray-100">

        <!-- Top accent bar -->
        <div class="top-bar fixed top-0 left-0 right-0 z-50"></div>

        <div class="min-h-screen flex flex-col md:flex-row pt-1">

            {{-- Left branding panel — hidden on small screens, shown on md+ --}}
            <div class="hidden md:flex miit-panel md:w-2/5 lg:w-1/3 flex-col items-center justify-center px-10 py-16 text-white text-center">
                <img src="{{ asset('Images/miit-logo.png') }}" alt="UniKL MIIT Logo" class="w-28 h-28 object-contain mb-6 drop-shadow-lg">
                <h1 class="text-2xl font-extrabold tracking-wide mb-1">UniKL MIIT</h1>
                <p class="text-sm font-semibold mb-6" style="color: var(--miit-gold);">
                    Malaysia Institute of Information Technology
                </p>
                <div class="border-t border-white/20 pt-6 w-full">
                    <p class="text-base font-bold mb-1">STRG Request System</p>
                    <p class="text-xs text-blue-200 leading-relaxed">
                        Short Term Research Grant<br>
                        Workflow &amp; Approval Management
                    </p>
                </div>
                <div class="mt-auto pt-10 text-xs text-blue-200/60">
                    © {{ date('Y') }} Universiti Kuala Lumpur
                </div>
            </div>

            {{-- Right form panel --}}
            <div class="flex-1 flex flex-col">

                {{-- Mobile-only header --}}
                <header class="md:hidden bg-white border-b px-4 py-3 flex items-center gap-3" style="border-bottom: 2px solid var(--miit-blue);">
                    <img src="{{ asset('Images/miit-logo.png') }}" alt="UniKL MIIT" class="h-9 w-auto">
                    <div class="leading-tight">
                        <p class="text-sm font-extrabold" style="color: var(--miit-blue);">UniKL MIIT</p>
                        <p class="text-xs text-gray-500">STRG Request System</p>
                    </div>
                    <nav class="ml-auto flex gap-2 text-sm font-medium">
                        <a href="{{ url('/') }}" class="px-3 py-1.5 rounded text-gray-600 hover:bg-gray-100 text-xs">Home</a>
                        @guest
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded text-xs font-semibold" style="color: var(--miit-blue);">Login</a>
                        @endguest
                    </nav>
                </header>

                {{-- Desktop top nav --}}
                <div class="hidden md:flex items-center justify-end px-8 py-4 bg-white border-b border-gray-200">
                    <nav class="flex items-center gap-3 text-sm font-medium">
                        <a href="{{ url('/') }}" class="px-3 py-1.5 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-100">← Home</a>
                        @guest
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded font-semibold" style="color: var(--miit-blue);">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-1.5 rounded-lg text-white font-semibold text-sm"
                                   style="background: var(--miit-blue);">
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
