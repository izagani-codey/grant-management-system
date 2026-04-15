<x-guest-layout>
    <div class="bg-white border border-slate-200 shadow-lg rounded-2xl px-6 py-7 sm:px-8">
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-slate-900">Verify Your Email</h1>
            <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                Thanks for registering! Before continuing, please verify your email address by clicking the link we sent to your UniKL email. If you didn't receive it, click below to resend.
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm font-medium text-green-700">
                A new verification link has been sent to your email address.
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 mt-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-gray-500 hover:text-gray-700">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
