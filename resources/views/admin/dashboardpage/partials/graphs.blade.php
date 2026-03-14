<div class="grid grid-cols-1 md:grid-cols-2 gap-6 justify-items-center bg-[#F7FBF9]/50 rounded-2xl shadow-lg border-t-8 border-[#1a8a44] p-4">
    
    <div class="flex flex-col h-full">
        <h2 class="text-xl font-black text-[#003918] uppercase tracking-tighter mb-8 items-center md:text-center">
            Enrollment Progress Overview
        </h2>
        
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <span class="w-32 text-gray-500 font-medium text-[12px] text-right leading-tight">Total Registrations</span>
                <div class="flex-1 flex items-center gap-3">
                    <div class="bg-[#048F81] h-7 rounded-md" style="width: 100%"></div>
                    <span class="text-gray-500 font-bold shrink-0 text-[12px]">{{ number_format($totalRegistrations) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <span class="w-32 text-gray-500 font-medium text-[12px] text-right leading-tight">Document Verified</span>
                <div class="flex-1 flex items-center gap-3">
                    <div class="bg-[#00923F] h-7 rounded-md" style="width: {{ $percVerified }}%"></div>
                    <span class="text-[#00923F] font-bold shrink-0 text-[12px]">{{ number_format($totalSubmissions) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <span class="w-32 text-gray-500 font-medium text-[12px] text-right leading-tight">Officially Enrolled</span>
                <div class="flex-1 flex items-center gap-3">
                    <div class="bg-[#00568d] h-7 rounded-md" style="width: {{ $percEnrolled }}%"></div>
                    <span class="text-gray-500 font-bold shrink-0 text-[12px]">{{ number_format($totalEnrolled) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col items-center bg-transparent">
        <h2 class="text-xl font-black text-[#003918] uppercase tracking-tighter mb-4 text-center">
            Student Elective Preference
        </h2>
        <div class="relative w-full max-w-[130px] h-[130px] mx-auto">
            <canvas id="electiveChart"></canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span class="text-[9px] text-gray-400 uppercase tracking-widest font-bold">Total</span>
                <span class="text-2xl font-black text-slate-800 leading-none">{{ array_sum($electiveCounts) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-x-6 gap-y-2 mt-4 text-[10px] font-bold uppercase text-slate-600">
            <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-[#00568d] mr-2"></span> STEM</div>
            <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-[#00897b] mr-2"></span> ASSH</div>
            <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-[#1a8a44] mr-2"></span> BE</div>
            <div class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-[#facc15] mr-2"></span> TECHPRO</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('electiveChart').getContext('2d');
        
        // Feed data from PHP
        const counts = [
            {{ $electiveCounts['STEM'] ?? 0 }}, 
            {{ $electiveCounts['ASSH'] ?? 0 }}, 
            {{ $electiveCounts['BE'] ?? 0 }}, 
            {{ $electiveCounts['TechPro'] ?? 0 }}
        ];

        const total = counts.reduce((a, b) => a + b, 0);

        // If no data, show a gray ring instead of an empty canvas
        const chartData = total > 0 ? counts : [1];
        const chartColors = total > 0 
            ? ['#00568d', '#00897b', '#1a8a44', '#facc15'] 
            : ['#e5e7eb']; // Light gray for empty state

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['STEM', 'ASSH', 'BE', 'TechPro'],
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%', // Thinner ring looks more modern
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: total > 0,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw;
                                let percentage = ((value / total) * 100).toFixed(1);
                                return ` ${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                layout: {
                    padding: 10
                }
            }
        });
    });
</script>