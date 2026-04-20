<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Checklist Management</h1>
                <p class="text-gray-600 mt-1">Define verification checklists for Staff1 reviewers</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-sm font-semibold rounded-full shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    Admin
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Request Type Checklists</h3>
                    <button type="button" 
                            onclick="openAddItemModal()"
                            class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Checklist Item
                    </button>
                </div>

                @foreach($requestTypes as $requestType)
                    <div class="mb-8 last:mb-0">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">{{ $requestType->name }}</h4>
                            <div class="text-sm text-gray-600">
                                {{ $requestType->checklistItems->count() }} items
                                @if($requestType->checklistItems->where('is_required', true)->count() > 0)
                                    <span class="text-orange-600">({{ $requestType->checklistItems->where('is_required', true)->count() }} required)</span>
                                @endif
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            @if($requestType->checklistItems->count() > 0)
                                <div id="sortable-{{ $requestType->id }}" class="space-y-1">
                                    @foreach($requestType->checklistItems as $item)
                                        <div class="checklist-item flex items-center justify-between p-4 bg-white hover:bg-gray-50 border-b border-gray-200 last:border-b-0" 
                                             data-item-id="{{ $item->id }}">
                                            <div class="flex items-center space-x-3 flex-1">
                                                <div class="cursor-move text-gray-400 hover:text-gray-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="font-medium text-gray-900">{{ $item->label }}</span>
                                                        @if($item->is_required)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">Required</span>
                                                        @endif
                                                        @if(!$item->is_active)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-500 mt-1">
                                                        Order: {{ $item->sort_order }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button type="button"
                                                        onclick="editItem({{ $item->id }}, {{ json_encode($item->label) }}, {{ $item->is_required ? 'true' : 'false' }}, {{ $item->is_active ? 'true' : 'false' }}, {{ $item->sort_order }}, {{ $item->request_type_id }})"
                                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                    Edit
                                                </button>
                                                <form action="{{ route('admin.checklists.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this checklist item?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-8 text-center">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No checklist items</h3>
                                    <p class="text-gray-600">Add checklist items to define what Staff1 should verify for this request type.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="itemModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="itemForm" action="{{ route('admin.checklists.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="item_id" id="itemId">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Add Checklist Item</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="request_type_id" class="block text-sm font-medium text-gray-700">Request Type</label>
                                <select name="request_type_id" id="request_type_id" required
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                                    @foreach($requestTypes as $requestType)
                                        <option value="{{ $requestType->id }}">{{ $requestType->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="label" class="block text-sm font-medium text-gray-700">Checklist Item Label</label>
                                <input type="text" name="label" id="label" required maxlength="255"
                                       placeholder="e.g., Proof of enrollment attached"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="sort_order" class="block text-sm font-medium text-gray-700">Sort Order</label>
                                <input type="number" name="sort_order" id="sort_order" min="0" value="0"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500">Lower numbers appear first in the checklist</p>
                            </div>

                            <div class="flex items-center space-x-6">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_required" id="is_required" value="1" checked
                                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <label for="is_required" class="ml-2 block text-sm text-gray-700">Required item</label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" checked
                                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Item
                        </button>
                        <button type="button" onclick="closeItemModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
let currentEditId = null;

function openAddItemModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Add Checklist Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemForm').action = '{{ route('admin.checklists.store') }}';
    document.getElementById('itemForm').method = 'POST';
    document.getElementById('itemId').value = '';
    
    // Remove method field if it exists
    const methodField = document.querySelector('input[name="_method"]');
    if (methodField) methodField.remove();
    
    document.getElementById('itemModal').classList.remove('hidden');
}

function editItem(id, label, isRequired, isActive, sortOrder, request_type_id) {
    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'Edit Checklist Item';
    
    // Set form values
    document.getElementById('request_type_id').value = request_type_id;
    document.getElementById('label').value = label;
    document.getElementById('sort_order').value = sortOrder;
    document.getElementById('is_required').checked = isRequired;
    document.getElementById('is_active').checked = isActive;
    
    // Update form action and method
    document.getElementById('itemForm').action = '{{ route('admin.checklists.update', ':id') }}'.replace(':id', id);
    document.getElementById('itemForm').method = 'POST';
    document.getElementById('itemId').value = id;
    
    // Add PUT method field
    let methodField = document.querySelector('input[name="_method"]');
    if (!methodField) {
        methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'PUT';
        document.getElementById('itemForm').appendChild(methodField);
    }
    
    document.getElementById('itemModal').classList.remove('hidden');
}

function closeItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
    document.getElementById('itemForm').reset();
    currentEditId = null;
}

// Initialize sortable functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if Sortable is available
    if (typeof Sortable === 'undefined') {
        console.error('Sortable.js library not loaded');
        return;
    }
    
    // Initialize Sortable.js for each request type's checklist
    @foreach($requestTypes as $requestType)
        @if($requestType->checklistItems->count() > 0)
            new Sortable(document.getElementById('sortable-{{ $requestType->id }}'), {
                animation: 150,
                handle: '.cursor-move',
                onEnd: function(evt) {
                    const items = Array.from(evt.to.children).map((child, index) => ({
                        id: child.dataset.itemId,
                        sort_order: index
                    }));
                    
                    fetch('{{ route('admin.checklists.reorder') }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ items: items })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Checklist items reordered successfully');
                        }
                    })
                    .catch(error => console.error('Error reordering checklist items:', error));
                }
            });
        @endif
    @endforeach
});
</script>

@if($requestTypes->some(fn($rt) => $rt->checklistItems->count() > 0))
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endif
