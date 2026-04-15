<x-guest-layout>
    <div class="bg-white border border-slate-200 shadow-lg rounded-2xl px-6 py-7 sm:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
            <p class="mt-1 text-sm text-slate-500">All fields marked <span class="text-red-500">*</span> are required.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            {{-- Personal Info --}}
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-3">Personal Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" :value="__('Full Name *')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="staff_id" :value="__('Staff ID *')" />
                        <x-text-input id="staff_id" class="block mt-1 w-full" type="text" name="staff_id" :value="old('staff_id')" required placeholder="e.g. UNIKL123456" />
                        <x-input-error :messages="$errors->get('staff_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('UniKL Email *')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required placeholder="name@unikl.edu.my" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone Number *')" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" required placeholder="e.g. 0123456789" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                </div>
            </div>

            {{-- Staff / Academic Info --}}
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-3">Staff Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="designation" :value="__('Designation *')" />
                        <select id="designation" name="designation" required
                            class="block mt-1 w-full border-gray-300 focus:border-blue-900 focus:ring-blue-900 rounded-md shadow-sm text-sm"
                            <option value="">— Select Designation —</option>
                            <optgroup label="Academic">
                                <option value="Professor" @selected(old('designation') === 'Professor')>Professor</option>
                                <option value="Associate Professor" @selected(old('designation') === 'Associate Professor')>Associate Professor</option>
                                <option value="Senior Lecturer" @selected(old('designation') === 'Senior Lecturer')>Senior Lecturer</option>
                                <option value="Lecturer" @selected(old('designation') === 'Lecturer')>Lecturer</option>
                                <option value="Assistant Lecturer" @selected(old('designation') === 'Assistant Lecturer')>Assistant Lecturer</option>
                                <option value="Research Fellow" @selected(old('designation') === 'Research Fellow')>Research Fellow</option>
                                <option value="Postdoctoral Researcher" @selected(old('designation') === 'Postdoctoral Researcher')>Postdoctoral Researcher</option>
                            </optgroup>
                            <optgroup label="Administrative">
                                <option value="Director" @selected(old('designation') === 'Director')>Director</option>
                                <option value="Deputy Director" @selected(old('designation') === 'Deputy Director')>Deputy Director</option>
                                <option value="Manager" @selected(old('designation') === 'Manager')>Manager</option>
                                <option value="Assistant Manager" @selected(old('designation') === 'Assistant Manager')>Assistant Manager</option>
                                <option value="Executive" @selected(old('designation') === 'Executive')>Executive</option>
                                <option value="Assistant Executive" @selected(old('designation') === 'Assistant Executive')>Assistant Executive</option>
                                <option value="Officer" @selected(old('designation') === 'Officer')>Officer</option>
                            </optgroup>
                            <optgroup label="Technical">
                                <option value="Senior Technologist" @selected(old('designation') === 'Senior Technologist')>Senior Technologist</option>
                                <option value="Technologist" @selected(old('designation') === 'Technologist')>Technologist</option>
                                <option value="Technical Assistant" @selected(old('designation') === 'Technical Assistant')>Technical Assistant</option>
                            </optgroup>
                            <option value="Other" @selected(old('designation') === 'Other')>Other</option>
                        </select>
                        <x-input-error :messages="$errors->get('designation')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="department" :value="__('Department / Faculty / Centre *')" />
                        <x-text-input id="department" class="block mt-1 w-full" type="text" name="department" :value="old('department')" required placeholder="e.g. Faculty of Engineering" />
                        <x-input-error :messages="$errors->get('department')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="employee_level" :value="__('Employee Level / Grade (optional)')" />
                        <x-text-input id="employee_level" class="block mt-1 w-full" type="text" name="employee_level" :value="old('employee_level')" placeholder="e.g. DH52, DS52, VU7" />
                        <x-input-error :messages="$errors->get('employee_level')" class="mt-1" />
                        <p class="text-xs text-slate-400 mt-1">Your pay grade or civil service level if applicable.</p>
                    </div>
                </div>
            </div>

            {{-- Password --}}
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-3">Set Password</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="password" :value="__('Password *')" />
                        <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password *')" />
                        <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                    </div>
                </div>
            </div>

            <x-primary-button class="w-full justify-center">
                {{ __('Create Account') }}
            </x-primary-button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-600">
            Already registered? <a class="font-semibold text-blue-600 hover:underline" href="{{ route('login') }}">Log in</a>
        </p>
    </div>
</x-guest-layout>