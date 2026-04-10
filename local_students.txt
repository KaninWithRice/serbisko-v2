@extends('admin.layout')

@section('page_title', 'Students')

@section('header_content')
<form action="{{ route('admin.students') }}" method="GET" class="relative w-full max-w-[350px] group"> 
    {{-- Maintain other filters while searching --}}
    @foreach(request()->except('search') as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach

    <div class="relative flex items-center w-full">
        <input type="text" 
               id="studentSearchInput" {{-- CRITICAL: Added ID for JS --}}
               name="search" 
               value="{{ request('search') }}"
               placeholder="Search Name or LRN" 
               autocomplete="off"
               class="w-full bg-[#F1F3F2] border-none rounded-full py-2 pl-5 pr-16 focus:ring-2 focus:ring-[#00923F] outline-none text-sm text-gray-600 placeholder-gray-400 font-regular">
        
        <div class="absolute right-1.5 flex items-center gap-1">
            {{-- THE CLEAR BUTTON --}}
            @if(request('search'))
                <button type="button" 
                        id="clearSearchBtn" {{-- Added ID for JS --}}
                        class="p-1 text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif

            {{-- SEARCH BUTTON --}}
            <button type="submit" class="bg-[#00923F] p-1.5 rounded-full cursor-pointer hover:bg-[#007a35] transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </div>
    </div>
</form>
@endsection

@section('content')
    <div class="space-y-1">
        @include('admin.studentpage.partials.tabs')

        @include('admin.studentpage.partials.filter-bar')

        <div class="mt-4"> {{-- This is the container the AJAX swaps --}}
            @include('admin.studentpage.partials.student-table')
        </div>
    </div>
@endsection

<script>
    /**
     * Master function to update the table
     */
    function updateStudentTable(url) {
        // We target the outer wrapper in the main view
        const tableWrapper = document.querySelector('.mt-4');
        if (!tableWrapper) return;

        tableWrapper.style.opacity = '0.5';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            // This replaces the content inside .mt-4 with the partial
            tableWrapper.innerHTML = html;
            tableWrapper.style.opacity = '1';
        })
        .catch(error => {
            console.error('Fetch error:', error);
            tableWrapper.style.opacity = '1';
        });
    }

    /**
     * Global Tab Switcher
     */
    function switchTab(tabName) {
        const url = new URL(window.location.href);
        if (tabName && tabName !== 'All') {
            url.searchParams.set('status', tabName);
        } else {
            url.searchParams.delete('status');
        }
        url.searchParams.delete('page'); // Reset pagination

        window.history.pushState({}, '', url);
        updateStudentTable(url);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('studentSearchInput');
        const clearBtn = document.getElementById('clearSearchBtn');
        let timeout = null;

        // 1. Live Search Logic
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const url = new URL(window.location.href);
                    const val = searchInput.value.trim();
                    val ? url.searchParams.set('search', val) : url.searchParams.delete('search');
                    
                    window.history.pushState({}, '', url);
                    updateStudentTable(url);
                }, 300);
            });
        }

        // 2. AJAX Sort Interceptor
        document.addEventListener('click', function(e) {
            const sortLink = e.target.closest('#sortMenu a');
            if (sortLink) {
                e.preventDefault();
                const url = new URL(sortLink.href);
                window.history.pushState({}, '', url);
                updateStudentTable(url);
            }
        });

        // 3. Clear Button Logic
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                window.history.pushState({}, '', url);
                updateStudentTable(url);
            });
        }
    });

    // Helper for the dropdown toggle (keep this global)
    function toggleSortMenu() {
        const menu = document.getElementById('sortMenu');
        if (menu) menu.classList.toggle('hidden');
    }


        /**
     * Generic filter applier
     * @param {string} param - The URL parameter to set (e.g., 'grade_level')
     * @param {string} value - The value from the select dropdown
     */
    function applyFilter(param, value) {
        const url = new URL(window.location.href);

        if (value) {
            url.searchParams.set(param, value);
        } else {
            url.searchParams.delete(param);
        }

        // Always reset to page 1 when a filter changes
        url.searchParams.delete('page');

        // Update the browser's address bar
        window.history.pushState({}, '', url);

        // Fetch the new data
        updateStudentTable(url);
    }
</script>