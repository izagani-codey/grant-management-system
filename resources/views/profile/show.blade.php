<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Profile') }}
            </h2>
            <button onclick="saveProfile()" 
               class="inline-flex items-center px-4 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg hover:bg-blue-950 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Profile
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Profile Overview Card --}}
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-8">
                    <div class="flex items-center">
                        <div class="bg-white rounded-full p-4">
                            <svg class="w-12 h-12 text-blue-900" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-6 text-white">
                            <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                            <p class="text-indigo-100">{{ $user->email }}</p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/20 text-white">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Personal Information --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                Personal Information
                            </h3>
                            <form id="profileForm" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                    <dd class="text-sm text-gray-900">
                                        <input type="text" name="name" value="{{ $user->name }}" 
                                               class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500">
                                    </dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="text-sm text-gray-900">{{ $user->email }}</dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Role</dt>
                                    <dd class="text-sm text-gray-900">{{ ucfirst($user->role) }}</dd>
                                </div>
                            </form>
                        </div>
                        
                        {{-- Professional Information --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                                </svg>
                                Professional Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Staff ID</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($user->staff_id)
                                            {{ $user->staff_id }}
                                            <span class="text-xs text-gray-500 ml-2">(Cannot be changed)</span>
                                        @else
                                            <input type="text" name="staff_id" value="{{ old('staff_id', $user->staff_id) }}" 
                                                   class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="Enter staff ID">
                                        @endif
                                    </dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Designation</dt>
                                    <dd class="text-sm text-gray-900">
                                        <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" 
                                               class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="e.g., Lecturer, Administrator">
                                    </dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Department</dt>
                                    <dd class="text-sm text-gray-900">
                                        <input type="text" name="department" value="{{ old('department', $user->department) }}" 
                                               class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="e.g., Faculty of Engineering">
                                    </dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                    <dd class="text-sm text-gray-900">
                                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                                               class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="+60123456789">
                                    </dd>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Employee Level</dt>
                                    <dd class="text-sm text-gray-900">
                                        <input type="text" name="employee_level" value="{{ old('employee_level', $user->employee_level) }}" 
                                               class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="e.g., Senior Executive">
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Profile Completion Status --}}
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Profile Completion</h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if($user->hasCompleteProfile())
                                        Your profile is complete! All required information has been provided.
                                    @else
                                        Please complete your profile information to ensure smooth request processing.
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center">
                                @if($user->hasCompleteProfile())
                                    <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('profile.edit') }}" 
                   class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-900">Edit Profile</h3>
                            <p class="text-xs text-gray-500">Update your information</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('profile.edit') }}#update-password" 
                   class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-900">Change Password</h3>
                            <p class="text-xs text-gray-500">Update your password</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('dashboard') }}" 
                   class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-900">Dashboard</h3>
                            <p class="text-xs text-gray-500">Back to dashboard</p>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
function saveProfile() {
    const form = document.getElementById('profileForm');
    const saveButton = document.querySelector('button[onclick="saveProfile()"]');
    const originalHTML = saveButton.innerHTML;

    saveButton.innerHTML = 'Saving...';
    saveButton.disabled = true;

    const fd = new FormData(form);
    fd.append('_method', 'PATCH');

    // pull in the other profile fields from the professional info section
    const inputs = document.querySelectorAll('[name="designation"],[name="department"],[name="phone"],[name="employee_level"]');
    inputs.forEach(input => fd.set(input.name, input.value));

    fetch('{{ route("profile.update") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: fd,
    })
    .then(response => {
        if (response.ok) {
            showNotification('Profile updated successfully!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            return response.json().then(data => {
                const messages = data.errors
                    ? Object.values(data.errors).flat().join('\n')
                    : (data.message || 'Something went wrong.');
                showNotification(messages, 'error');
            });
        }
    })
    .catch(() => showNotification('Network error. Please try again.', 'error'))
    .finally(() => {
        saveButton.innerHTML = originalHTML;
        saveButton.disabled = false;
    });
}

function showNotification(message, type) {
    // Remove any existing notifications
    const existingNotification = document.querySelector('.notification-toast');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 flex items-center space-x-3 transform transition-all duration-300 translate-x-full`;
    
    if (type === 'success') {
        notification.classList.add('bg-green-500', 'text-white');
        notification.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>${message}</span>
        `;
    } else {
        notification.classList.add('bg-red-500', 'text-white');
        notification.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span>${message}</span>
        `;
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Auto-save functionality (optional)
let autoSaveTimer;
document.addEventListener('input', function(e) {
    if (e.target.matches('#profileForm input')) {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            // Uncomment to enable auto-save
            // saveProfile();
        }, 2000); // Auto-save after 2 seconds of inactivity
    }
});
</script>
@endpush
