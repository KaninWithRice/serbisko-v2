<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade Level</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Submitted</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($pendingScans as $scan)
        <tr>
            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                {{ $scan->first_name }} {{ $scan->last_name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                {{ $scan->display_grade }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                    {{ $scan->document_type }}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm">
                {{ \Carbon\Carbon::parse($scan->created_at)->diffForHumans() }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                <button onclick="openModal('{{ asset('storage/' . $scan->file_path) }}')" class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200 transition">
                    View Image
                </button>
                
                <form action="{{ route('admin.verification.action') }}" method="POST" class="inline-block verification-form">
                    @csrf
                    <input type="hidden" name="scan_id" value="{{ $scan->id }}">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition">Approve</button>
                </form>

                <form action="{{ route('admin.verification.action') }}" method="POST" class="inline-block verification-form">
                    @csrf
                    <input type="hidden" name="scan_id" value="{{ $scan->id }}">
                    <input type="hidden" name="action" value="decline">
                    <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Decline</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                No documents pending verification.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
