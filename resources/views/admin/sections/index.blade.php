@extends('admin.layout')

@section('page_title', 'Sections')

@section('header_content')
<div class="flex items-center gap-3">
    <div class="text-right">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Active School Year</p>
        <p class="text-[#003918] font-black text-sm">{{ $activeSY }}</p>
    </div>
    <div class="h-8 w-[1px] bg-gray-300 mx-2"></div>
    <div class="bg-[#00923F] p-2 rounded-full shadow-sm">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6 font-sans tracking-tight" x-data="{ 
    quantity: 1,
    academic_year: '{{ $activeSY }}',
    grade_level: 'Grade 11'
}">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-[#00923F] text-[#003918] px-6 py-4 rounded-xl shadow-sm mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-[#00923F]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <p class="text-sm font-bold">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-xl shadow-sm mb-6 flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
            <p class="text-sm font-bold">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Creation Panel -->
        <div class="lg:col-span-4 bg-[#F7FBF9]/60 backdrop-blur-md rounded-3xl shadow-xl shadow-green-900/5 border border-green-100/50 p-8 h-fit sticky top-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="bg-[#005288] p-2 rounded-lg shadow-md">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h2 class="text-[#005288] text-lg font-black uppercase tracking-tight">Bulk Create</h2>
            </div>
            
            <form action="{{ route('admin.sections.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Academic Year -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Academic Year</label>
                        <div class="relative group">
                            <input type="text" name="academic_year" x-model="academic_year" list="ay-list"
                                class="w-full bg-[#F1F3F2] border-none rounded-xl py-3 px-5 focus:ring-2 focus:ring-[#00923F] outline-none text-sm text-[#003918] font-bold transition-all"
                                placeholder="e.g. 2026-2027">
                            <datalist id="ay-list">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <!-- Grade Level -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Year Level</label>
                        <div class="relative">
                            <select name="grade_level" x-model="grade_level"
                                class="w-full bg-[#F1F3F2] border-none rounded-xl py-3 px-5 focus:ring-2 focus:ring-[#00923F] outline-none text-sm text-[#003918] font-bold appearance-none transition-all cursor-pointer">
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Number of Sections</label>
                        <input type="number" x-model.number="quantity" min="1" max="20"
                            class="w-full bg-[#F1F3F2] border-none rounded-xl py-3 px-5 focus:ring-2 focus:ring-[#00923F] outline-none text-sm text-[#003918] font-bold transition-all">
                    </div>
                </div>

                <!-- Dynamic Names -->
                <div class="space-y-3 pt-2">
                    <label class="block text-[10px] font-black text-[#005288] uppercase tracking-widest ml-1">Section Designations</label>
                    <div class="max-h-[350px] overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                        <template x-for="i in parseInt(quantity || 0)" :key="i">
                            <div class="relative animate-fadeIn">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-[11px] font-black text-gray-500" x-text="i"></div>
                                <input type="text" name="section_names[]" required
                                    :placeholder="'Enter name for section ' + i"
                                    class="w-full bg-[#F1F3F2] border-none rounded-xl py-3.5 pl-10 pr-5 focus:ring-2 focus:ring-[#005288] outline-none text-sm text-[#003918] font-black uppercase shadow-sm transition-all placeholder-gray-400">
                            </div>
                        </template>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full bg-[#00923F] hover:bg-[#007a34] text-white font-black py-4 rounded-2xl shadow-lg shadow-green-900/20 transition-all uppercase text-xs tracking-[0.2em] mt-4 active:scale-[0.98]">
                    Generate Sections
                </button>
            </form>
        </div>

        <!-- List Table -->
        <div class="lg:col-span-8 bg-white rounded-[2rem] shadow-2xl shadow-gray-200/50 border border-gray-50 overflow-hidden flex flex-col">
            <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-[#F7FBF9]/30">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center text-[#00923F]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[#003918] text-xl font-black uppercase tracking-tight">Active Inventory</h2>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Management & Overview</p>
                    </div>
                </div>
                <div class="bg-[#005288]/5 px-4 py-2 rounded-xl border border-[#005288]/10">
                    <span class="text-[10px] font-black text-[#005288] uppercase tracking-widest">Total Count:</span>
                    <span class="ml-1 text-sm font-black text-[#005288]">{{ count($sections) }}</span>
                </div>
            </div>
            
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/30">
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] border-b border-gray-50">Academic Year</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] border-b border-gray-50">Year Level</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] border-b border-gray-50">Designation</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] border-b border-gray-50 text-right">Options</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($sections as $section)
                            <tr class="hover:bg-[#F7FBF9]/50 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-green-400 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <span class="text-[13px] font-bold text-[#003918]">{{ $section->academic_year }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-[10px] font-black px-3 py-1.5 rounded-lg bg-[#005288]/5 text-[#005288] border border-[#005288]/10 uppercase tracking-wider">{{ $section->grade_level }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-[14px] font-black text-[#003918] uppercase tracking-tight">{{ $section->name }}</span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <form action="{{ route('admin.sections.destroy', $section->id) }}" method="POST" onsubmit="return confirm('Archive this section designation?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2.5 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all active:scale-90">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="w-20 h-20 bg-gray-50 rounded-[2rem] flex items-center justify-center text-gray-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-400 uppercase tracking-[0.2em]">No records found</p>
                                            <p class="text-[10px] text-gray-300 font-bold uppercase mt-1">Start by adding sections in the side panel</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out forwards;
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
</style>
@endsection
