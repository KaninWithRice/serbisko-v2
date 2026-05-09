<div x-data="{ activeTab: '{{ request('status', 'All') }}' }" class="flex justify-between items-end pt-3 border-b border-gray-400">
    <div class="flex gap-3">
        @foreach(['All', 'Registered', 'Partial Compliance', 'For Enrollment', 'Enrolled'] as $tab)
            <button 
                @click="activeTab = '{{ $tab }}'; switchTab('{{ $tab }}')"
                :class="activeTab === '{{ $tab }}' ? 'text-[#005288] border-[#005288] font-bold' : 'text-gray-500 border-transparent font-medium'"
                class="pb-3 px-2 transition-all duration-200 text-sm border-b-4 -mb-[1px]">
                {{ $tab }}
            </button>
        @endforeach
    </div>

    <div class="flex items-center gap-4 pb-3"> 
        
        @php
            $lastSync = DB::table('sync_histories')->where('status', 'Success')->latest()->first();
        @endphp
        
        @if($lastSync)
            <span class="text-[10px] text-gray-400 mb-0.5">
                Last updated: {{ \Carbon\Carbon::parse($lastSync->created_at)->diffForHumans() }}
            </span>
        @endif

        <x-school-year-selector onchange="applyFilter('school_year', year)" />

        <div class="flex items-center gap-2">
            {{-- Export Button --}}
            <button class="flex items-center gap-2 px-4 py-1.5 bg-[#005288] text-white font-semibold text-xs rounded-lg hover:bg-[#003d66] transition-colors shadow-sm">                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm1 8V3.5L18.5 10H15z"/>
                </svg>
                Export as Excel
            </button>
        </div>
    </div>
</div>