<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2.0, user-scalable=yes">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Grant Request System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Brand Theme -->
@php
    $primaryColor = $settings['primary_color']->value ?? '#003087';
    $accentColor = $settings['accent_color']->value ?? '#C8971E';
@endphp
<style>
    :root {
        --primary-color: {{ $primaryColor }};
        --accent-color: {{ $accentColor }};
        --light-accent: #E8F0FB;
    }

    /* Top accent bar */
    body::before {
        content: '';
        display: block;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color) 70%, var(--accent-color) 100%);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;
    }

    /* Offset content for the accent bar */
    body > div.min-h-screen {
        padding-top: 4px;
    }

    /* Nav brand override */
    nav {
        border-bottom: 2px solid var(--primary-color) !important;
    }

    /* Card header accent */
    .card-header-brand {
        background: linear-gradient(135deg, var(--primary-color), #1a4fa0);
        color: white;
        border-radius: 0.5rem 0.5rem 0 0;
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        font-size: 0.95rem;
        letter-spacing: 0.01em;
    }

    /* Status badge gold accent */
    .badge-gold {
        background: var(--accent-color);
        color: #1a1a1a;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 0.72rem;
    }

    /* Primary button override */
    .btn-brand {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-brand:hover { background: #002070; }

    /* Page section cards — subtle left border */
    .brand-card {
        border-left: 4px solid var(--primary-color);
        border-radius: 0 0.5rem 0.5rem 0;
    }
</style>
        <!-- iPad Optimization -->
        <style>
            /* iPad-specific optimizations */
            @media (min-width: 768px) and (max-width: 1024px) {
                body { font-size: 16px; }
                .touch-target { min-height: 44px; min-width: 44px; }
            }
            
            /* Touch-friendly interactions */
            .touch-target {
                min-height: 44px;
                min-width: 44px;
                padding: 8px;
            }
            
            /* Smooth scrolling for iPad */
            @media (min-width: 768px) {
                html { scroll-behavior: smooth; }
            }
        </style>
    </head>
    <body class="font-sans antialiased overflow-x-hidden bg-gray-50">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow-sm border-b border-gray-200">
                    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-8">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
