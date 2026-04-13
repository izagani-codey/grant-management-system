<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm">
    @php
        $unreadNotifications = auth()->user()->notifications()->where('is_read', false)->count();
    @endphp
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 md:h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="touch-target flex items-center">
                        <x-application-logo class="block h-8 md:h-10 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex md:space-x-1 lg:space-x-8 md:ms-8 lg:ms-10">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if(auth()->user()->role === 'admission')
                        <x-nav-link :href="route('requests.create')" :active="request()->routeIs('requests.create')">
                            {{ __('New Request') }}
                        </x-nav-link>
                    @endif

                    <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                        <div class="flex items-center gap-2 touch-target px-2 py-2">
                            <span aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2Zm6-6V11c0-3.1-1.6-5.7-4.5-6.3V4c0-.8-.7-1.5-1.5-1.5S10.5 3.2 10.5 4v.7C7.6 5.3 6 7.9 6 11v5l-2 2v1h16v-1l-2-2Z"/>
                                </svg>
                            </span>
                            <span class="text-sm md:text-base">{{ __('Notifications') }}</span>
                            @if(($unreadNotifications ?? 0) > 0)
                                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-600 text-white">
                                    {{ $unreadNotifications }}
                                </span>
                            @endif
                        </div>
                    </x-nav-link>

                    @if(auth()->user()->role === 'admin')
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            {{ __('Admin Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.deployment-playbook')" :active="request()->routeIs('admin.deployment-playbook')">
                            {{ __('Secure Deploy') }}
                        </x-nav-link>
                    @endif

                    {{-- Dean navigation hidden for now
                    @if(auth()->user()->role === 'dean')
                        <x-nav-link :href="route('dean.dashboard')" :active="request()->routeIs('dean.*')">
                            {{ __('Dean Dashboard') }}
                        </x-nav-link>
                    @endif
                    --}}

                    @if(in_array(auth()->user()->role, ['staff1', 'staff2', 'admin'], true))
                        <x-nav-link :href="route('form-templates.index')" :active="request()->routeIs('form-templates.*')">
                            {{ __('Blank Forms') }}
                        </x-nav-link>
                    @endif

                    <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                        <span class="touch-target px-2 py-2 text-sm md:text-base">{{ __('Profile') }}</span>
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if(auth()->user()->role === 'admission')
                <x-responsive-nav-link :href="route('requests.create')" :active="request()->routeIs('requests.create')">
                    {{ __('New Request') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Notifications') }}</span>
                    @if(($unreadNotifications ?? 0) > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white">
                            {{ $unreadNotifications }}
                        </span>
                    @endif
                </div>
            </x-responsive-nav-link>

            @if(auth()->user()->role === 'admin')
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    {{ __('Admin Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.deployment-playbook')" :active="request()->routeIs('admin.deployment-playbook')">
                    {{ __('Secure Deploy') }}
                </x-responsive-nav-link>
            @endif

            @if(in_array(auth()->user()->role, ['staff1', 'staff2', 'admin'], true))
                <x-responsive-nav-link :href="route('form-templates.index')" :active="request()->routeIs('form-templates.*')">
                    {{ __('Blank Forms') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                {{ __('Profile') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
