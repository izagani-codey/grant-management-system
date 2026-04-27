<x-guest-layout>
    <div class="bg-white border border-slate-200 shadow-lg rounded-2xl px-6 py-7 sm:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Reset Your Password</h1>
            <p class="mt-1 text-sm text-slate-500">
                Enter your email address and we'll send you a reset link.
            </p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                    :value="old('email')" required autofocus placeholder="name@example.edu" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between gap-4 pt-1">
                <a href="{{ route('login') }}" class="text-sm font-medium hover:underline" style="color: #003087;">
                    ← Back to login
                </a>
                <x-primary-button>
                    Send Reset Link
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
