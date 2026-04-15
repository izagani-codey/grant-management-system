<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UniKL MIIT — Grant Request Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        :root {
            --miit-blue: #003087;
            --miit-gold: #C8971E;
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--miit-blue) 0%, #1a4fa0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .gold-text {
            color: var(--miit-gold);
        }
        .hero-gradient {
            background: linear-gradient(135deg, var(--miit-blue) 0%, #1a4fa0 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(0,48,135,0.12);
        }
        .top-bar {
            height: 4px;
            background: linear-gradient(90deg, var(--miit-blue) 70%, var(--miit-gold) 100%);
        }
        /* iPad optimizations */
        @media (min-width: 768px) and (max-width: 1024px) {
            .hero-title { font-size: 2.5rem; }
            .touch-target { min-height: 44px; min-width: 44px; }
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Top accent bar -->
    <div class="top-bar"></div>

    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50" style="border-bottom: 2px solid var(--miit-blue);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('Images/miit-logo.png') }}" alt="UniKL MIIT Logo" class="h-10 w-auto">
                    <div class="flex flex-col leading-tight">
                        <span class="font-extrabold text-sm tracking-wide" style="color: var(--miit-blue);">UniKL MIIT</span>
                        <span class="text-xs text-gray-500 font-medium">Request Management System</span>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="text-white px-5 py-2 rounded-lg font-semibold text-sm transition-colors"
                           style="background: var(--miit-blue);">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="font-medium text-sm px-3 py-2 rounded transition-colors"
                           style="color: var(--miit-blue);">
                            Sign In
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="text-white px-5 py-2 rounded-lg font-semibold text-sm transition-colors"
                               style="background: var(--miit-blue);">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('Images/miit-logo.png') }}" alt="UniKL MIIT" class="h-20 w-auto opacity-90">
                </div>
                <p class="text-sm font-semibold uppercase tracking-widest mb-3" style="color: var(--miit-gold);">
                    Universiti Kuala Lumpur — MIIT
                </p>
                <h1 class="hero-title text-4xl md:text-5xl font-bold mb-6 leading-tight">
                    Short Term Research Grant<br>
                    <span style="color: var(--miit-gold);">Request Management System</span>
                </h1>
                <p class="text-lg md:text-xl mb-10 text-blue-100 max-w-2xl mx-auto">
                    A fully digital workflow for grant request submission, multi-level approval, and document generation — from submission to final sign-off.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @guest
                        <a href="{{ route('login') }}"
                           class="touch-target font-semibold px-8 py-3 rounded-lg inline-block transition-colors"
                           style="background: var(--miit-gold); color: #1a1a1a;">
                            Sign In to Continue
                        </a>
                    @endguest
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="touch-target font-semibold px-8 py-3 rounded-lg inline-block"
                           style="background: var(--miit-gold); color: #1a1a1a;">
                            Go to Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Complete <span class="gradient-text">Workflow Solution</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Streamlined processes for every stage of grant request management
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Request Submission -->
                <div class="card-hover bg-white p-8 rounded-xl shadow-md border border-gray-100">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-6" style="background: #E8F0FB;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--miit-blue);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Submit Requests</h3>
                    <ul class="space-y-3 text-gray-600 text-sm">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Online submission with dynamic forms
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Real-time request tracking
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Digital signature integration
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Automatic PDF generation
                        </li>
                    </ul>
                </div>

                <!-- Review Process -->
                <div class="card-hover bg-white p-8 rounded-xl shadow-md border border-gray-100">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-6" style="background: #FBF5E8;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--miit-gold);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Review &amp; Approve</h3>
                    <ul class="space-y-3 text-gray-600 text-sm">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Multi-level verification workflow
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Priority request management
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Override capabilities for urgent cases
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Comprehensive audit trails
                        </li>
                    </ul>
                </div>

                <!-- Admin -->
                <div class="card-hover bg-white p-8 rounded-xl shadow-md border border-gray-100">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-6" style="background: #E8F5ED;">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">System Administration</h3>
                    <ul class="space-y-3 text-gray-600 text-sm">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            User &amp; role management
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Request type &amp; template management
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Supporting document upload
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Reporting &amp; audit log access
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    How It <span class="gradient-text">Works</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    A simple 4-step process from submission to final approval
                </p>
            </div>

            <div class="grid md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: #E8F0FB;">
                        <span class="text-2xl font-bold" style="color: var(--miit-blue);">1</span>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Submit Request</h3>
                    <p class="text-gray-500 text-sm">Fill out the form with details, VOT items, and digital signature</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: #E8F0FB;">
                        <span class="text-2xl font-bold" style="color: var(--miit-blue);">2</span>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Staff Verification</h3>
                    <p class="text-gray-500 text-sm">Staff 1 verifies and forwards to the recommending officer</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: #E8F0FB;">
                        <span class="text-2xl font-bold" style="color: var(--miit-blue);">3</span>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Recommendation</h3>
                    <p class="text-gray-500 text-sm">Staff 2 reviews, signs, and recommends for final approval</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: #FBF5E8;">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20" style="color: var(--miit-gold);">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Dean Approval</h3>
                    <p class="text-gray-500 text-sm">Dean signs off and the approved PDF is generated automatically</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold mb-2" style="color: var(--miit-blue);">100%</div>
                    <p class="text-gray-500 font-medium">Digital Process — No Paperwork</p>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2" style="color: var(--miit-blue);">24/7</div>
                    <p class="text-gray-500 font-medium">Accessible Online Anytime</p>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2" style="color: var(--miit-gold);">3 Levels</div>
                    <p class="text-gray-500 font-medium">Multi-Stage Approval Workflow</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 hero-gradient text-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                Ready to Submit Your Request?
            </h2>
            <p class="text-lg mb-8 text-blue-100">
                Sign in with your UniKL MIIT account to get started with the STRG system.
            </p>
            @guest
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('login') }}"
                       class="font-semibold px-8 py-3 rounded-lg inline-block transition-colors"
                       style="background: var(--miit-gold); color: #1a1a1a;">
                        Sign In
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white transition-colors inline-block"
                           style="hover-color: var(--miit-blue);">
                            Create Account
                        </a>
                    @endif
                </div>
            @endguest
            @auth
                <a href="{{ route('dashboard') }}"
                   class="font-semibold px-8 py-3 rounded-lg inline-block"
                   style="background: var(--miit-gold); color: #1a1a1a;">
                    Go to Dashboard
                </a>
            @endauth
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('Images/miit-logo.png') }}" alt="UniKL MIIT" class="h-10 w-auto opacity-80">
                    <div>
                        <p class="font-bold text-sm" style="color: var(--miit-gold);">UniKL MIIT</p>
                        <p class="text-gray-400 text-xs">Short Term Research Grant System</p>
                    </div>
                </div>
                <p class="text-gray-500 text-sm">
                    © {{ date('Y') }} Universiti Kuala Lumpur. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
