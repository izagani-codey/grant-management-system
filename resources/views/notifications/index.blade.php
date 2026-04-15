<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent">Notifications</h1>
                <p class="text-gray-600 mt-1">Stay updated with your request activities</p>
            </div>
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                @method('PATCH')
                <button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Mark All as Read
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center mb-6">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900">Recent Notifications</h3>
                        <div class="flex items-center space-x-2 text-sm text-gray-600">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span>{{ auth()->user()->notifications()->where('is_read', false)->count() }} unread</span>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-200">
                    @forelse($notifications as $notification)
                        <a href="{{ route('notifications.open', $notification->id) }}"
                           class="block p-6 transition-all hover:bg-gray-50 {{ $notification->is_read ? 'bg-white' : 'bg-blue-50 border-l-4 border-blue-500' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0">
                                        @if($notification->is_read)
                                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-lg font-semibold text-gray-900 {{ $notification->is_read ? 'font-normal' : 'font-bold' }}">
                                            {{ $notification->title }}
                                        </p>
                                        <p class="text-gray-600 mt-1">{{ $notification->message }}</p>
                                        @if($notification->url)
                                            <div class="mt-2">
                                                <span class="text-xs text-blue-600 font-medium">View Details →</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <span class="text-sm text-gray-500">{{ $notification->created_at->format('M j, Y') }}</span>
                                    <div class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications yet</h3>
                            <p class="text-gray-600">You're all caught up! New notifications will appear here.</p>
                        </div>
                    @endforelse
                </div>

                @if($notifications->hasPages())
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            {{ $notifications->links() }}
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ $notifications->firstItem() }}</span>
                                    to
                                    <span class="font-medium">{{ $notifications->lastItem() }}</span>
                                    of
                                    <span class="font-medium">{{ $notifications->total() }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                {{ $notifications->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
