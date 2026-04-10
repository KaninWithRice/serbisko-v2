<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Student Name</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Grade Level</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Document Type</th>
            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rejected At</th>
            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($rejectedPapers as $rej)
        <tr class="hover:bg-red-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                {{ $rej->last_name }}, {{ $rej->first_name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $rej->display_grade }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 border border-orange-200">
                    {{ $rej->document_type }}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-mono">
                {{ \Carbon\Carbon::parse($rej->rejected_at)->format('M d, g:i A') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <form action="{{ route('admin.collect-rejected-paper') }}" method="POST" onsubmit="return confirm('Mark this paper as collected?')">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $rej->user_id }}">
                    <input type="hidden" name="rejected_at" value="{{ $rej->rejected_at }}">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-4 rounded-full text-xs shadow-sm transition-all transform hover:scale-105">
                        Collect
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">
                No physical paper rejections recorded.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
