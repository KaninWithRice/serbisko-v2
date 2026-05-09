@php
    // BUG 1 FIX: Combine all sources of school years to ensure past years appear
    $studentYears = \App\Models\Student::distinct()->pluck('school_year')->toArray();
    $formYears = \App\Models\CustomForm::withTrashed()->distinct()->pluck('school_year')->toArray();
    $sectionYears = \App\Models\Section::distinct()->pluck('academic_year')->toArray();
    
    // Merge, unique, and sort descending
    $years = collect(array_merge($studentYears, $formYears, $sectionYears))
        ->unique()
        ->filter()
        ->values();

    $activeSY = \App\Models\Student::activeYear();
    
    // Ensure active year is always in the list even if no data exists yet
    if (!$years->contains($activeSY)) {
        $years->push($activeSY);
    }
    
    $years = $years->sortDesc();
    $selectedYear = request('school_year', $activeSY);
@endphp

{{-- BUG 3 FIX: Added mt-0.5 to give room for the focus ring at the top --}}
<div x-data="{ 
    open: false, 
    selected: '{{ $selectedYear }}',
    activeYear: '{{ $activeSY }}',
    toggle() { this.open = !this.open },
    select(year) {
        this.selected = year;
        this.open = false;
        (function(year) {
            {{ $onchange ?? '' }}
        })(year);
    }
}" class="relative inline-block text-left mt-0.5" @click.away="open = false">
    <div class="flex items-center gap-2">
        {{-- BUG 2 FIX: Added whitespace-nowrap to prevent label wrapping --}}
        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">School Year</label>
        <button @click="toggle()" type="button" 
                class="inline-flex justify-between items-center w-full px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg font-semibold transition-all hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#005288] min-w-[120px]">
            <span x-text="selected + (selected === activeYear ? ' (Active)' : '')"></span>
            <svg class="w-3 h-3 ml-2 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>

    <div x-show="open" 
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-2xl overflow-hidden focus:outline-none"
         style="z-index: 9999;">
        <div class="py-1 border border-gray-100">
            @foreach($years as $year)
                <button @click="select('{{ $year }}')" 
                        type="button"
                        :class="selected === '{{ $year }}' ? 'bg-[#005288]/5 text-[#005288]' : 'text-gray-700'"
                        class="block w-full text-left px-4 py-2 text-xs font-semibold hover:bg-gray-50 hover:text-[#005288] transition-colors">
                    {{ $year }} {{ $year == $activeSY ? '(Active)' : '' }}
                </button>
            @endforeach
        </div>
    </div>
</div>
