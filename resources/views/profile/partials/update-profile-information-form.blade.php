<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Staff Profile Information --}}
        <div class="border-t pt-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Staff Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="staff_id" :value="__('Staff ID')" />
                    <x-text-input id="staff_id" name="staff_id" type="text" class="mt-1 block w-full" :value="old('staff_id', $user->staff_id)" :readonly="$user->staff_id" placeholder="Enter your staff ID" autocomplete="off" />
                    <x-input-error class="mt-2" :messages="$errors->get('staff_id')" />
                    @if($user->staff_id)
                        <p class="text-xs text-gray-500 mt-1">Staff ID cannot be changed once set</p>
                    @endif
                </div>

                <div>
                    <x-input-label for="designation" :value="__('Designation')" />
                    <x-text-input id="designation" name="designation" type="text" class="mt-1 block w-full" :value="old('designation', $user->designation)" placeholder="e.g., Lecturer, Administrator" autocomplete="organization-title" />
                    <x-input-error class="mt-2" :messages="$errors->get('designation')" />
                </div>

                <div>
                    <x-input-label for="department" :value="__('Department')" />
                    <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $user->department)" placeholder="e.g., Faculty of Engineering" autocomplete="organization" />
                    <x-input-error class="mt-2" :messages="$errors->get('department')" />
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Phone Number')" />
                    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" placeholder="+60123456789" autocomplete="tel" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                </div>

                <div>
                    <x-input-label for="employee_level" :value="__('Employee Level')" />
                    <x-text-input id="employee_level" name="employee_level" type="text" class="mt-1 block w-full" :value="old('employee_level', $user->employee_level)" placeholder="e.g., Executive, Senior Executive, Management" autocomplete="off" />
                    <x-input-error class="mt-2" :messages="$errors->get('employee_level')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
