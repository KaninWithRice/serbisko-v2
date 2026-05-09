<div class="grid grid-cols-1 md:grid-cols-2 gap-6 justify-items-center bg-[#F7FBF9]/50 rounded-2xl shadow-lg border-t-8 border-[#1a8a44] p-4">
    
    <div class="flex flex-col h-full w-full">
        <h2 class="text-xl font-black text-[#003918] uppercase tracking-tighter mb-8 items-center md:text-center">
            Enrollment Progress Overview
        </h2>
        
        <div class="space-y-6">
            {{-- Total Registrations --}}
            <div class="flex flex-col gap-1.5">
                <div class="flex justify-between items-center text-[11px] font-bold uppercase tracking-tight text-gray-500">
                    <span>Total Registrations</span>
                    <span>{{ number_format($totalRegistrations) }} ({{ $totalRegistrations > 0 ? '100' : '0' }}%)</span>
                </div>
                <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden">
                    <div class="bg-[#00796B] h-full rounded-full transition-all duration-1000 ease-out" 
                        x-data="{ width: 0 }" 
                        x-init="setTimeout(() => width = {{ $totalRegistrations > 0 ? 100 : 0 }}, 100)" 
                        :style="`width: ${width}%`"
                        style="width: 0%"></div>
                </div>
            </div>

            {{-- Document Verified --}}
            <div class="flex flex-col gap-1.5">
                <div class="flex justify-between items-center text-[11px] font-bold uppercase tracking-tight text-gray-500">
                    <span>Document Verified</span>
                    <span>{{ number_format($totalSubmissions) }} ({{ round($percVerified) }}%)</span>
                </div>
                <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden">
                    <div class="bg-[#26A69A] h-full rounded-full transition-all duration-1000 ease-out" 
                        x-data="{ width: 0 }" 
                        x-init="setTimeout(() => width = {{ $percVerified }}, 100)" 
                        :style="`width: ${width}%`"
                        style="width: 0%"></div>
                </div>
            </div>

            {{-- Officially Enrolled --}}
            <div class="flex flex-col gap-1.5">
                <div class="flex justify-between items-center text-[11px] font-bold uppercase tracking-tight text-gray-500">
                    <span>Officially Enrolled</span>
                    <span>{{ number_format($totalEnrolled) }} ({{ round($percEnrolled) }}%)</span>
                </div>
                <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden">
                    <div class="bg-[#66BB6A] h-full rounded-full transition-all duration-1000 ease-out" 
                        x-data="{ width: 0 }" 
                        x-init="setTimeout(() => width = {{ $percEnrolled }}, 100)" 
                        :style="`width: ${width}%`"
                        style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{ 
            label: '',
            hoverCount: '',
            init() {
                window.addEventListener('update-center', (e) => {
                    this.label = e.detail.label || '';
                    this.hoverCount = e.detail.count !== undefined ? e.detail.count : '';
                });
            }
        }" class="flex flex-col items-center bg-transparent">
        <h2 class="text-xl font-black text-[#003918] uppercase tracking-tighter mb-4 text-center">
            Student Elective Preference
        </h2>
        <div class="relative w-full max-w-[200px] h-[200px] mx-auto">
            {{-- DATA BRIDGE: We pass the counts to the parent script via data attribute because scripts in innerHTML don't execute --}}
            <canvas id="electiveChart" data-counts='@json($electiveCounts)'></canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none px-4 text-center">
                <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold leading-tight" x-text="label"></span>
                <span class="text-xl font-black text-slate-800 leading-none mt-1" x-text="hoverCount"></span>
            </div>
        </div>
    </div>
</div>
