<div class="bg-white rounded-2xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <svg class="w-6 h-6 mr-2 {{ $colorClasses['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                {{ $title }}
            </h3>
            <p class="text-gray-600 text-sm mt-1">{{ $description }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
            Clear all filters
        </a>
    </div>
    
    <form method="GET" action="{{ route('dashboard') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="{{ $role === 'admission' ? 'Reference, description...' : 'Reference, applicant, email...' }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm {{ $colorClasses['focus'] }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm {{ $colorClasses['focus'] }}">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Request Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm {{ $colorClasses['focus'] }}">
                    <option value="">All Types</option>
                    @foreach($requestTypes as $type)
                        <option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm {{ $colorClasses['focus'] }}">
                    <option value="">All Priority</option>
                    <option value="1" @selected(request('priority') === '1')">High Priority</option>
                    <option value="0" @selected(request('priority') === '0')">Normal</option>
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <div class="flex space-x-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           placeholder="From" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm {{ $colorClasses['focus'] }}">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           placeholder="To" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm {{ $colorClasses['focus'] }}">
                </div>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full text-white px-4 py-2 rounded-lg font-medium transition-colors {{ $colorClasses['button'] }}">
                    Apply Filters
                </button>
            </div>
        </div>
    </form>
</div>

