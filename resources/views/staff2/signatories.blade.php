<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Signatories Management</h1>
            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Staff 2</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Import CSV/Excel Section --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                    <h2 class="text-base font-bold text-gray-900">Import Signatories</h2>
                    <p class="text-xs text-gray-500 mt-0.5">CSV format: title, name, designation, department, staff_id</p>
                </div>
                <div class="p-6">
                    <form action="{{ route('staff2.signatories.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-4">
                        @csrf
                        <input type="file" name="file" accept=".csv,.xlsx,.xls" required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded hover:bg-blue-700 transition">
                            Import
                        </button>
                    </form>
                </div>
            </div>

            {{-- Add New Signatory Form --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-base font-bold text-gray-900">Add New Signatory</h2>
                </div>
                <div class="p-6">
                    <form action="{{ route('staff2.signatories.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" name="title" placeholder="Dr. / Mr. / Mrs. / Prof."
                                   class="w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <input type="text" name="name" required
                                   class="w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Designation *</label>
                            <input type="text" name="designation" required placeholder="HoRI / Director / Dean etc"
                                   class="w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" name="department"
                                   class="w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Staff ID</label>
                            <input type="text" name="staff_id"
                                   class="w-full rounded border-gray-300 text-sm">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="is_active" value="1" checked>
                                Is Active
                            </label>
                            <button type="submit" class="ml-auto px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded hover:bg-blue-700 transition">
                                Add Signatory
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Signatories Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-base font-bold text-gray-900">Existing Signatories</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Designation</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Department</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Staff ID</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Active</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($signatories as $signatory)
                                <tr id="row-{{ $signatory->id }}" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600">{{ $signatory->title ?: '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $signatory->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $signatory->designation }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $signatory->department ?: '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $signatory->staff_id ?: '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($signatory->is_active)
                                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                                        @else
                                            <span class="inline-block w-2 h-2 rounded-full bg-gray-300"></span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button" onclick="editSignatory({{ $signatory->id }})"
                                                class="text-xs text-blue-600 hover:underline font-medium">
                                            Edit
                                        </button>
                                        <form action="{{ route('staff2.signatories.destroy', $signatory->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this signatory?')"
                                              class="inline-block ml-2">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                
                                {{-- Edit Row (hidden by default) --}}
                                <tr id="edit-{{ $signatory->id }}" class="hidden bg-blue-50">
                                    <td colspan="7" class="px-4 py-4">
                                        <form action="{{ route('staff2.signatories.update', $signatory->id) }}" method="POST"
                                              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @csrf @method('PUT')
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Title</label>
                                                <input type="text" name="title" value="{{ $signatory->title }}"
                                                       class="w-full rounded border-gray-300 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Name *</label>
                                                <input type="text" name="name" value="{{ $signatory->name }}" required
                                                       class="w-full rounded border-gray-300 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Designation *</label>
                                                <input type="text" name="designation" value="{{ $signatory->designation }}" required
                                                       class="w-full rounded border-gray-300 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Department</label>
                                                <input type="text" name="department" value="{{ $signatory->department }}"
                                                       class="w-full rounded border-gray-300 text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Staff ID</label>
                                                <input type="text" name="staff_id" value="{{ $signatory->staff_id }}"
                                                       class="w-full rounded border-gray-300 text-xs">
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <label class="flex items-center gap-1 text-xs text-gray-600">
                                                    <input type="checkbox" name="is_active" value="1" {{ $signatory->is_active ? 'checked' : '' }}>
                                                    Is Active
                                                </label>
                                                <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs font-semibold rounded hover:bg-green-700 transition">
                                                    Save
                                                </button>
                                                <button type="button" onclick="cancelEdit({{ $signatory->id }})"
                                                        class="px-3 py-1 bg-gray-500 text-white text-xs font-semibold rounded hover:bg-gray-600 transition">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($signatories->isEmpty())
                        <div class="px-6 py-10 text-center text-gray-500">
                            <p class="text-sm">No signatories found. Add your first signatory above.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script>
        function editSignatory(id) {
            document.getElementById('row-' + id).classList.add('hidden');
            document.getElementById('edit-' + id).classList.remove('hidden');
        }

        function cancelEdit(id) {
            document.getElementById('row-' + id).classList.remove('hidden');
            document.getElementById('edit-' + id).classList.add('hidden');
        }
    </script>
</x-app-layout>
