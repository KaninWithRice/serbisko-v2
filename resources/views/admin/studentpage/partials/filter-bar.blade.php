<div x-data="{ 
    openFilter: null, 
    selectedFilters: { 
        grade: '{{ request('grade_level') }}', 
        student_type: '{{ request('student_type') }}', 
        track: '{{ request('track') }}', 
        cluster: '{{ request('cluster') }}', 
        requirements_status: '{{ request('requirements_status') }}' 
    },
    
    applyFilters() {
        let url = new URL(window.location.href);
        const keyMap = {
            grade: 'grade_level',
            student_type: 'student_type',
            track: 'track',
            cluster: 'cluster',
            requirements_status: 'requirements_status'
        };

        Object.keys(this.selectedFilters).forEach(key => {
            const value = this.selectedFilters[key];
            const urlKey = keyMap[key] || key;
            if (value) {
                url.searchParams.set(urlKey, value);
            } else {
                url.searchParams.delete(urlKey);
            }
        });

        // 1. Update the URL in the browser bar without reloading
        window.history.pushState({}, '', url);
        
        // 2. Call your existing AJAX function from students.blade.php
        if (typeof updateStudentTable === 'function') {
            updateStudentTable(url);
        }
    },

    resetFilter(key) { 
        this.selectedFilters[key] = ''; 
        this.applyFilters();
    },

    resetAll() {
        // Clear local state
        Object.keys(this.selectedFilters).forEach(k => this.selectedFilters[k] = '');
        
        // Create clean URL
        let url = new URL(window.location.origin + window.location.pathname);
        
        // Preserve search if you want, or clear it too:
        // url.searchParams.delete('search'); 

        window.history.pushState({}, '', url);
        updateStudentTable(url);
    }
}" @click.away="openFilter = null" class="relative">

    <div class="flex items-center justify-between w-full py-3 border-b border-gray-400 -mb-1">
        <div class="flex items-center gap-2">
            <template x-for="(options, key) in { 
                grade: ['Grade 11', 'Grade 12'], 
                student_type: ['Regular', 'Transferee', 'ALS Graduate', 'Balik-Aral'],
                track: ['Academic', 'TechPro'], 
                cluster: ['STEM', 'ASSH', 'BE', 'TechPro'],
                requirements_status: ['Complete', 'Incomplete'] 
            }">
                <div class="relative">
                    <button @click="openFilter = (openFilter === key ? null : key)" 
                            :class="selectedFilters[key] ? 'border-gray-400 ring-1 ring-gray-400' : 'border-gray-400 text-gray-500'"
                            class="flex items-center gap-2 px-2 py-1 border text-[10px] font-bold rounded-full uppercase tracking-tight transition-all duration-300 bg-white">
                        
                        <div x-show="selectedFilters[key]" 
                             @click.stop="resetFilter(key)"
                             class="flex items-center justify-center bg-black text-white rounded-full p-0.5 hover:bg-red-600 cursor-pointer">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>

                        <div class="flex items-center gap-1">
                            <template x-if="!selectedFilters[key]">
                                <span class="text-gray-500" x-text="{grade: 'Grade Level', requirements_status: 'Requirement Status', student_type: 'Student Type'}[key] || key.charAt(0).toUpperCase() + key.slice(1)"></span>
                            </template>
                            <template x-if="selectedFilters[key]">
                                <span class="text-[#005288]" x-text="selectedFilters[key]"></span>
                            </template>
                        </div>

                        <svg x-show="!selectedFilters[key]" 
                             class="w-3 h-3 transition-transform duration-300" 
                             :class="openFilter === key ? 'rotate-180' : ''" 
                             fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                             <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="openFilter === key" class="absolute z-50 mt-2 w-48 bg-white border shadow-xl rounded-lg py-2">
                        <template x-for="option in options">
                            <button @click="selectedFilters[key] = option; openFilter = null; applyFilters()" 
                                    class="block w-full text-left px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 hover:text-[#005288]" 
                                    x-text="option"></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <button @click="resetAll()" 
                class="text-[10px] font-bold text-gray-500 hover:text-red-600 transition-colors uppercase tracking-tight whitespace-nowrap">
            Clear Filters
        </button>
    </div>
</div>