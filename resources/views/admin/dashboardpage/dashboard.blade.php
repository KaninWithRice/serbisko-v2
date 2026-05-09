@extends('admin.layout')

@section('page_title', 'Dashboard')

@section('content')
<div x-data="{ 
    loading: false, 
    currentGrade: '{{ request('grade_level', '') }}',
    currentYear: '{{ request('school_year', \App\Models\Student::activeYear()) }}',
    fetchDashboard(grade, year) {
        this.loading = true;
        if (grade !== undefined) this.currentGrade = grade;
        if (year !== undefined) this.currentYear = year;
        
        let url = new URL('{{ route('admin.dashboard') }}');
        if(this.currentGrade) url.searchParams.set('grade_level', this.currentGrade);
        if(this.currentYear) url.searchParams.set('school_year', this.currentYear);

        window.history.pushState({}, '', url);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('main-dashboard-content').innerHTML = html;
            
            // CRITICAL: Re-trigger Chart.js initialization
            document.dispatchEvent(new CustomEvent('dashboardUpdated'));
            
            this.loading = false;
        });
    }
}" @refresh-dashboard.window="fetchDashboard(currentGrade, currentYear)">

    <div class="sticky top-0 z-10 bg-white py-2 flex justify-between items-center mb-4"> 
        <div class="inline-flex rounded-xl shadow-md border border-gray-100 bg-[#F7FBF9]">
            @foreach(['All' => '', 'Grade 11' => 'Grade 11', 'Grade 12' => 'Grade 12'] as $label => $value)
                <button 
                    @click="fetchDashboard('{{ $value }}')"
                    :class="currentGrade === '{{ $value }}' ? 'bg-[#00568d] text-white' : 'text-[#00568d] hover:text-[#00568d]/50'"
                    class="px-6 py-2 rounded-xl font-semibold text-sm transition-all shadow-sm">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <x-school-year-selector onchange="Alpine.find($el.closest('[x-data]')).fetchDashboard(undefined, year)" />
    </div>

    <div id="main-dashboard-content" class="relative min-h-[400px]">
        <div x-show="loading" 
            x-transition:enter="transition opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak 
            class="absolute inset-0 bg-white/60 z-50 flex items-center justify-center rounded-2xl backdrop-blur-[1px]">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#003918]"></div>
        </div>

        @include('admin.dashboardpage.partials._dashboard_wrapper')
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
    /**
     * GLOBAL CHART RENDERER
     * Placed in parent because <script> tags injected via innerHTML do not execute.
     */
    function renderElectiveChart() {
        const canvas = document.getElementById('electiveChart');
        if (!canvas) return;

        let existingChart = Chart.getChart(canvas);
        if (existingChart) existingChart.destroy();

        // Retrieve data from the bridge attribute
        const dataMap = JSON.parse(canvas.getAttribute('data-counts') || '{}');

        const colorMap = {
            'ASSH': '#00897b',
            'BE':   '#1a8a44',
            'STEM': '#00568d',
            'CSS':  '#facc15',
            'VGD':  '#f97316',
            'EIM':  '#dc2626',
            'EPAS': '#7c3aed'
        };

        const labels    = Object.keys(dataMap);
        const counts    = labels.map(k => dataMap[k].count);
        const fullNames = labels.map(k => dataMap[k].name);
        const chartColors = labels.map(k => colorMap[k] || '#e5e7eb');
        const total     = counts.reduce((a, b) => a + b, 0);

        function dispatchCenter(label, count) {
            window.dispatchEvent(new CustomEvent('update-center', {
                detail: { label, count }
            }));
        }

        try {
            new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: total > 0 ? counts : [1],
                        backgroundColor: total > 0 ? chartColors : ['#e5e7eb'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    onHover: (event, chartElement) => {
                        if (total > 0 && chartElement.length > 0) {
                            const index = chartElement[0].index;
                            const count = counts[index];
                            dispatchCenter(
                                labels[index],
                                count + (count === 1 ? ' student' : ' students')
                            );
                        } else {
                            dispatchCenter('', '');
                        }
                    }
                }
            });
        } catch (err) {
            console.error("Chart.js rendering failed:", err);
        }
    }

    // Initial load
    document.addEventListener("DOMContentLoaded", renderElectiveChart);

    // Re-render after AJAX updates
    document.addEventListener('dashboardUpdated', renderElectiveChart);
    </script>
    @endsection