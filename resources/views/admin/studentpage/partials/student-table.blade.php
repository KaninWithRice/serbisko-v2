<div id="student-table-container" class="border-b border-gray-400">
    <table class="w-full bg-white table-fixed"> 
        <thead class="bg-white">
            <tr class="border-b border-gray-400">
                <th class="py-3 px-4 text-left text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[11%]">LRN</th>
                <th class="py-3 px-4 text-left text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[25%] relative">
                    <div class="flex items-center gap-1">
                        Full Name
                        <button onclick="toggleSortMenu()" class="hover:bg-gray-100 p-0.5 rounded transition-colors focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"><path fill="currentColor" d="m18 21l-4-4h3V7h-3l4-4l4 4h-3v10h3M2 19v-2h10v2M2 13v-2h7v2M2 7V5h4v2z"/></svg>
                        </button>
                    </div>
                    <div id="sortMenu" class="hidden absolute left-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-xl z-[50] normal-case font-medium">
                        <div class="py-1">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'az']) }}" class="block px-4 py-2 text-[10px] text-gray-700 hover:bg-[#f0faf4] hover:text-[#1a8a44] {{ request('sort') == 'az' ? 'bg-gray-100 font-bold' : '' }}">Sort A–Z</a>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'za']) }}" class="block px-4 py-2 text-[10px] text-gray-700 hover:bg-[#f0faf4] hover:text-[#1a8a44] {{ request('sort') == 'za' ? 'bg-gray-100 font-bold' : '' }}">Sort Z–A</a>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}" class="block px-4 py-2 text-[10px] text-gray-700 hover:bg-[#f0faf4] hover:text-[#1a8a44] {{ request('sort') == 'newest' ? 'bg-gray-100 font-bold' : '' }}">Newest first</a>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'oldest']) }}" class="block px-4 py-2 text-[10px] text-gray-700 hover:bg-[#f0faf4] hover:text-[#1a8a44] {{ request('sort') == 'oldest' ? 'bg-gray-100 font-bold' : '' }}">Oldest first</a>
                        </div>
                    </div>
                </th>
                <th class="py-3 px-4 text-center text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[10%]">Receipt #</th>
                <th class="py-3 px-4 text-center text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[10%]">Learner<br>Classification</th>
                <th class="py-3 px-4 text-center text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[7%]">Grade</th>
                <th class="py-3 px-4 text-center text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[8%]">Track &<br>Cluster</th>
                <th class="py-3 px-4 text-center text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[10%]">Status</th>
                <th class="py-3 px-4 text-center text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[10%]">Requirement</th>
                <th class="py-3 px-4 text-center text-[10px] font-bold uppercase tracking-wider text-[#003918] w-[7%]">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($students as $student)
                @php
                    $middleInitial = !empty($student->middle_name)
                        ? strtoupper(substr($student->middle_name, 0, 1)) . '.'
                        : '';
                    $firstNameTitle = ucwords(strtolower($student->first_name));
                    $extension = $student->extension_name ?? '';
                    $nameWithInitial = trim("{$firstNameTitle} {$middleInitial}");
                    $coloredPart = !empty($extension)
                        ? "{$nameWithInitial}, {$extension}"
                        : $nameWithInitial;

                    $clusterVal = strtoupper($student->display_cluster ?? '');
                    $clusterBadge = ['bg' => '#F1F3F2', 'text' => '#444441', 'label' => $student->display_cluster ?? '—'];
                    if (str_contains($clusterVal, 'ASSH'))      $clusterBadge = ['bg' => '#E0F2F1', 'text' => '#00897b', 'label' => 'ASSH'];
                    elseif (str_contains($clusterVal, 'BE'))    $clusterBadge = ['bg' => '#EAF3DE', 'text' => '#1a8a44', 'label' => 'BE'];
                    elseif (str_contains($clusterVal, 'STEM'))  $clusterBadge = ['bg' => '#E0EEF7', 'text' => '#00568d', 'label' => 'STEM'];
                    elseif (str_contains($clusterVal, 'CSS'))   $clusterBadge = ['bg' => '#FEF9C3', 'text' => '#a16207', 'label' => 'CSS'];
                    elseif (str_contains($clusterVal, 'VGD'))   $clusterBadge = ['bg' => '#FFEDD5', 'text' => '#f97316', 'label' => 'VGD'];
                    elseif (str_contains($clusterVal, 'EIM'))   $clusterBadge = ['bg' => '#FEE2E2', 'text' => '#dc2626', 'label' => 'EIM'];
                    elseif (str_contains($clusterVal, 'EPAS'))  $clusterBadge = ['bg' => '#EDE9FE', 'text' => '#7c3aed', 'label' => 'EPAS'];
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-4 text-[11px] text-[#003918] font-medium font-mono">{{ $student->lrn }}</td>
                    <td class="py-3 px-4 text-[11px] text-[#003918] font-bold truncate">
                        <span class="uppercase">{{ $student->last_name }},</span>
                        <span class="text-[#003918]/70 font-medium uppercase"> {{ $coloredPart }}</span>
                    </td>
                    <td class="py-3 px-4 text-[11px] text-center text-[#005288] font-bold tracking-tight truncate">
                        {{ $student->receipt_number ?? '—' }}
                    </td>
                    <td class="py-3 px-4 text-center text-[11px] text-[#003918]">{{ $student->display_status }}</td>
                    <td class="py-3 px-4 text-center text-[11px] text-[#003918]">{{ $student->display_grade }}</td>
                    <td class="py-3 px-4 text-center">
                        @if(!empty($student->display_track) && $student->display_track !== '—')
                            <div class="text-[10px] text-[#003918] font-medium mb-1">{{ $student->display_track }}</div>
                        @endif
                        @if(!empty($student->display_cluster) && $student->display_cluster !== '—')
                            <span style="background: {{ $clusterBadge['bg'] }}; color: {{ $clusterBadge['text'] }};"
                                class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full">
                                {{ $clusterBadge['label'] }}
                            </span>
                        @else
                            <span class="text-gray-400 text-[11px]">—</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="text-[10px] {{ $student->status_style }} px-2.5 py-1 rounded-full border font-bold">
                            {{ $student->enrollment_category }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="text-[11px] {{ $student->requirement_style }}">
                            {{ $student->requirement_display }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <a href="{{ route('admin.studentpage.profilepage', ['id' => $student->id]) }}"
                           class="text-[10px] font-bold uppercase tracking-wider text-[#1a8a44] hover:text-[#003918] hover:underline transition-colors">
                            Enroll
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="py-20 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-300">
                            <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">
                                {{ request('search') ? 'No records matching "' . request('search') . '"' : 'No records found' }}
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination Bar --}}
@if($students->hasPages())
<div class="flex items-center justify-between py-3 px-1">

    {{-- Showing X of Y results --}}
    <p class="text-[11px] text-gray-400 font-medium">
        Showing <span class="text-[#003918] font-bold">{{ $students->firstItem() }}–{{ $students->lastItem() }}</span> of <span class="text-[#003918] font-bold">{{ $students->total() }}</span> results
    </p>

    {{-- Page Buttons --}}
    <div class="flex items-center gap-1">

        {{-- Previous --}}
        @if($students->onFirstPage())
            <span class="px-3 py-1.5 text-[11px] font-semibold text-gray-300 cursor-not-allowed select-none">← Prev</span>
        @else
            <a href="javascript:void(0)"
               onclick="paginateTo({{ $students->currentPage() - 1 }})"
               class="px-3 py-1.5 text-[11px] font-semibold text-gray-500 hover:text-[#003918] transition-colors">
                ← Prev
            </a>
        @endif

        {{-- Page Numbers --}}
        @php
            $current = $students->currentPage();
            $last    = $students->lastPage();

            $window  = 2; // pages on each side of current
            $start   = max(1, $current - $window);
            $end     = min($last, $current + $window);
        @endphp

        {{-- First page + ellipsis --}}
        @if($start > 1)
            <a href="javascript:void(0)" onclick="paginateTo(1)"
               class="w-8 h-8 flex items-center justify-center rounded-full text-[11px] font-semibold text-gray-500 hover:bg-gray-100 hover:text-[#003918] transition-colors">1</a>
            @if($start > 2)
                <span class="w-8 h-8 flex items-center justify-center text-[11px] text-gray-400">…</span>
            @endif
        @endif

        {{-- Window pages --}}
        @for($page = $start; $page <= $end; $page++)
            @if($page === $current)
                <span class="w-8 h-8 flex items-center justify-center rounded-full text-[11px] font-bold text-white bg-[#003918]">
                    {{ $page }}
                </span>
            @else
                <a href="javascript:void(0)" onclick="paginateTo({{ $page }})"
                   class="w-8 h-8 flex items-center justify-center rounded-full text-[11px] font-semibold text-gray-500 hover:bg-gray-100 hover:text-[#003918] transition-colors">
                    {{ $page }}
                </a>
            @endif
        @endfor

        {{-- Ellipsis + last page --}}
        @if($end < $last)
            @if($end < $last - 1)
                <span class="w-8 h-8 flex items-center justify-center text-[11px] text-gray-400">…</span>
            @endif
            <a href="javascript:void(0)" onclick="paginateTo({{ $last }})"
               class="w-8 h-8 flex items-center justify-center rounded-full text-[11px] font-semibold text-gray-500 hover:bg-gray-100 hover:text-[#003918] transition-colors">
                {{ $last }}
            </a>
        @endif

        {{-- Next --}}
        @if($students->hasMorePages())
            <a href="javascript:void(0)"
               onclick="paginateTo({{ $students->currentPage() + 1 }})"
               class="px-3 py-1.5 text-[11px] font-semibold text-gray-500 hover:text-[#003918] transition-colors">
                Next →
            </a>
        @else
            <span class="px-3 py-1.5 text-[11px] font-semibold text-gray-300 cursor-not-allowed select-none">Next →</span>
        @endif

    </div>
</div>
@else
    {{-- Still show count even with a single page --}}
    @if($students->count())
    <div class="py-3 px-1 text-right">
        <p class="text-[11px] text-gray-400 font-medium">
            Showing <span class="text-[#003918] font-bold">{{ $students->count() }}</span> result{{ $students->count() !== 1 ? 's' : '' }}
        </p>
    </div>
    @endif
@endif

<script>
    function paginateTo(page) {
        if (typeof lastUserActionTime !== 'undefined') lastUserActionTime = Date.now();
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.history.pushState({}, '', url);
        if (typeof updateStudentTable === 'function') updateStudentTable(url);
    }
</script>