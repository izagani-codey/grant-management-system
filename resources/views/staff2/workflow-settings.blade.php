<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Workflow Settings
            </h2>
            <span class="text-sm text-gray-500">
                Staff 2 controls Dean-signature policy only
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="font-semibold text-gray-900">Request Type Signature Policy</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Applicant and Staff 2 signatures are always required. Toggle Dean signature per request type.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Request Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Current Layout</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Requests</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Dean Signature</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($requestTypes as $requestType)
                                @php($requiresDean = $requestType->workflowPolicy?->requires_dean_signature ?? true)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $requestType->name }}</div>
                                        @if($requestType->description)
                                            <div class="mt-1 text-sm text-gray-500">{{ $requestType->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $requiresDean ? 'three_signatures' : 'two_signatures' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $requestType->requests_count }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <form method="POST" action="{{ route('staff2.workflow.update', $requestType) }}" class="flex items-center gap-3">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="requires_dean_signature" value="{{ $requiresDean ? 0 : 1 }}">
                                            <span class="text-sm {{ $requiresDean ? 'text-green-700' : 'text-gray-500' }}">
                                                {{ $requiresDean ? 'Required' : 'Not required' }}
                                            </span>
                                            <button type="submit" class="rounded-md px-3 py-2 text-sm font-semibold {{ $requiresDean ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                                                {{ $requiresDean ? 'Switch to 2 signatures' : 'Switch to 3 signatures' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
