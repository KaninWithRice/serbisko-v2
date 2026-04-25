@extends('admin.layout')

@section('page_title')
    <div class="flex justify-center items-end w-full pb-2 font-['Inter'] tracking-normal">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-baseline space-x-2">
                <li><a href="{{ route('admin.students') }}" class="text-[16px] font-medium text-gray-500 hover:text-[#00923F] transition-colors">Students</a></li>
                <li class="flex text-[16px] font-bold text-[#00923F]">
                    <span class="mx-2 text-gray-400 select-none">></span>
                    <span>{{ $student->first_name }} {{ $student->last_name }} {{ $student->extension_name ? $student->extension_name : '' }}'s Enroll</span>
                </li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
{{-- Wrap the entire section in Alpine.js and a Form --}}
<div x-data="{ 
    editing: false, 
    isAutomating: false,
    automationStatus: '',
    automationColor: 'text-[#00923F]',
    academic_year: '{{ $activeSY }}',
    grade_level: '{{ $student->grade_level ?? $finalGrade }}',
    section_id: '{{ $student->section_id ?? "" }}',
    sections: [],
    studentData: {},
    async runAutomation() {
        try {
            this.isAutomating = true;
            this.automationStatus = 'Gathering enrollment data...';
            this.automationColor = 'text-[#00923F]';

            // 1. Determine the Section Name
            let selectedSectionName = '';
            if (this.section_id) {
                const found = this.sections.find(s => s.id == this.section_id);
                if (found) selectedSectionName = found.name;
            }
            if (!selectedSectionName) {
                selectedSectionName = '{{ addslashes($student->section_name ?? "") }}';
            }

            // 2. Validation
            if (!selectedSectionName || selectedSectionName === '—') {
                this.automationStatus = 'Error: Please assign and save a section first.';
                this.automationColor = 'text-red-600';
                this.isAutomating = false;
                return;
            }

            // 3. Gather full packet
            this.studentData = {
                lrn: '{{ addslashes($student->lrn ?? "") }}',
                first_name: '{{ addslashes($student->first_name ?? "") }}',
                last_name: '{{ addslashes($student->last_name ?? "") }}',
                middle_name: '{{ addslashes($student->middle_name ?? "") }}',
                extension_name: '{{ addslashes($student->extension_name ?? "") }}',
                birthday: '{{ addslashes($student->birthday ?? "") }}',
                section_name: selectedSectionName,
                grade_level: this.grade_level,
                mother_tongue: '{{ addslashes($student->mother_tongue ?? "") }}',
                sex: '{{ addslashes($student->sex ?? "") }}',
                curr_province: '{{ addslashes($student->curr_province ?? "") }}',
                curr_city: '{{ addslashes($student->curr_city ?? "") }}',
                curr_barangay: '{{ addslashes($student->curr_barangay ?? "") }}',
                curr_zip_code: '{{ addslashes($student->curr_zip_code ?? "") }}',
                mother_first_name: '{{ addslashes($student->mother_first_name ?? "") }}',
                mother_last_name: '{{ addslashes($student->mother_last_name ?? "") }}',
                mother_middle_name: '{{ addslashes($student->mother_middle_name ?? "") }}',
                father_first_name: '{{ addslashes($student->father_first_name ?? "") }}',
                father_last_name: '{{ addslashes($student->father_last_name ?? "") }}',
                father_middle_name: '{{ addslashes($student->father_middle_name ?? "") }}',
                guardian_first_name: '{{ addslashes($student->guardian_first_name ?? "") }}',
                guardian_last_name: '{{ addslashes($student->guardian_last_name ?? "") }}',
                guardian_middle_name: '{{ addslashes($student->guardian_middle_name ?? "") }}',
                guardian_relationship: '{{ addslashes($details['guardian_relationship'] ?? ($details['relationship'] ?? 'Relative')) }}',
                email: '{{ addslashes($details['email'] ?? ($details['email address'] ?? "")) }}',
                religion: '{{ addslashes($details['religion'] ?? "Roman Catholic") }}',
                citizenship: '{{ addslashes($details['citizenship'] ?? "Philippines") }}',
                modality: '{{ addslashes($details['modality'] ?? "Face to face") }}'
            };
            
            console.log('Final Data Packet Ready:', this.studentData);
            
            // Determine Service URL
            const hostname = window.location.hostname;
            const serviceUrl = `http://${hostname}:5002/fill-enrollment`;
            
            console.log(`[DEBUG] Attempting to fetch: ${serviceUrl}`);
            this.automationStatus = 'Connecting to automation service...';

            try {
                const response = await fetch(serviceUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.studentData)
                });
                
                const data = await response.json();

                if(data.status === 'started') {
                    this.automationStatus = 'Automation Started! Check the Firefox window.';
                    setTimeout(() => { this.isAutomating = false; this.automationStatus = ''; }, 15000);
                } else {
                    this.automationStatus = 'Error: ' + (data.message || 'Unknown error');
                    this.automationColor = 'text-red-600';
                    this.isAutomating = false;
                }
            } catch (fetchError) {
                console.error('[FETCH ERROR]', fetchError);
                this.automationStatus = `Connection Failed: Ensure automation service is running on port 5002.`;
                this.automationColor = 'text-red-600';
                this.isAutomating = false;
            }

        } catch (e) {
            console.error('JS Error:', e);
            this.automationStatus = 'Error: ' + e.message;
            this.automationColor = 'text-red-600';
            this.isAutomating = false;
        }
    },
    async fetchSections() {
        if (!this.academic_year || !this.grade_level) {
            this.sections = [];
            return;
        }
        try {
            let response = await fetch(`/admin/api/sections?academic_year=${encodeURIComponent(this.academic_year)}&grade_level=${this.grade_level}`);
            let data = await response.json();
            this.sections = data;
        } catch (e) {
            console.error('Failed to fetch sections', e);
        }
    }
}" 
x-init="fetchSections()"
class="p-6 font-['Inter'] tracking-normal space-y-4">

    @php
        $renderFields = function($fields, $cols = 'md:grid-cols-2', $isJson = false) {
            $html = "<div class='grid grid-cols-1 $cols gap-x-12 gap-y-6'>";
            foreach ($fields as $label => $value) {
                // Logic to create input names...
                $rawKey = strtolower(str_replace([':', ' ', "'", '#'], ['', '_', '', 'number'], $label));
                $inputName = $isJson ? "responses[$rawKey]" : $rawKey;
                
                // Handle array values (e.g. from multi-select checkboxes in Google Forms)
                if (is_array($value)) {
                    $val = implode(', ', $value);
                } else {
                    $val = $value ?: '';
                }

                $html .= "
                <div class='relative border-b-2 border-gray-200 pb-1 group hover:border-[#005288] transition-colors duration-200'>
                    <label class='block text-[10px] font-bold text-gray-400 mb-1'>$label</label>
                    <p x-show='!editing' class='text-[13px] uppercase text-[#003918] min-h-6'>".(e($val) ?: '—')."</p>
                    <input x-show='editing' type='text' name='$inputName' value='".e($val)."' 
                        class='w-full text-[13px] uppercase text-[#005288] bg-white border-none p-0 focus:ring-0 outline-none font-bold'>
                </div>";
            }
            $html .= "</div>";
            return $html;
        };
        
        // You can also calculate the age here since it's used later
        $age = !empty($student->birthday) ? \Carbon\Carbon::parse($student->birthday)->age : '—';
    @endphp
    
    <form action="{{ route('admin.students.update', $student->user_id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Add a hidden input for LRN to ensure it's still available if needed --}}
        <input type="hidden" name="id" value="{{ $student->lrn }}">

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-md shadow-sm mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-md shadow-sm mb-4">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-md shadow-sm mb-4">
                <p class="text-sm font-medium">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-5 flex justify-between items-center">
            <div>
                <h2 class="text-[#005288] text-2xl font-extrabold uppercase tracking-tight flex items-center gap-3">
                    {{ $student->first_name }} {{ $student->middle_name ? substr($student->middle_name, 0, 1) . '.' : '' }} {{ $student->last_name }} {{ $student->extension_name ? $student->extension_name : '' }}
                    
                    @if(isset($student->is_manually_edited) && $student->is_manually_edited)
                        <span class="text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-full border border-amber-200 font-black tracking-widest uppercase">
                            Locked from Sync
                        </span>
                    @endif
                </h2>
                <h3 class="text-gray-500 text-sm font-bold uppercase tracking-tight">{{ $student->lrn }}</h3>
            </div>

            <div class="flex items-center gap-3">
                @if(count($verifiedScans) > 0)
                    <button type="button" onclick="toggleDocsModal()" class="flex items-center gap-2 px-4 py-2.5 bg-[#005288] text-white rounded-lg font-bold text-sm hover:bg-[#003918] transition-colors shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        SCANNED DOCUMENTS
                    </button>
                @endif

                <button type="button" @click="editing = !editing" 
                    class="inline-flex items-center px-5 py-2.5 rounded-lg font-semibold shadow-sm transition outline-none border"
                    :class="editing ? 'bg-gray-100 text-gray-700 border-gray-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border-gray-300'">
                    <span x-text="editing ? 'Cancel' : 'Edit Profile'"></span>
                </button>

                <button x-show="!editing" type="button" 
                    :disabled="isAutomating || !{{ $isAllVerified ? 'true' : 'false' }}"
                    @click="runAutomation()"
                    class="inline-flex items-center px-5 py-2.5 bg-[#00923F] text-white rounded-lg font-semibold shadow-sm hover:bg-[#007a34] transition-colors border border-[#00923F] disabled:bg-gray-400 disabled:border-gray-400 disabled:cursor-not-allowed group relative"
                    title="{{ !$isAllVerified ? 'Cannot Enroll: Some required documents are not yet verified.' : '' }}">
                    <svg x-show="isAutomating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isAutomating ? 'Running...' : 'Enroll'"></span>

                    @if(!$isAllVerified)
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
                            Missing verified documents
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-gray-900"></div>
                        </div>
                    @endif
                </button>

                <div x-show="automationStatus" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-x-2"
                    x-transition:enter-end="opacity-100 transform translate-x-0"
                    class="text-xs font-bold uppercase tracking-wider" :class="automationColor" x-text="automationStatus">
                </div>
                
                <button x-show="editing" type="submit" 
                    class="inline-flex items-center bg-[#00923F] hover:bg-[#007a34] text-white font-bold px-5 py-2.5 rounded-lg shadow-md transition outline-none">
                    Save Changes
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">

            @php
            $age = '—';
            if (!empty($student->birthday)) {
                try { $age = \Carbon\Carbon::parse($student->birthday)->age; } catch (\Exception $e) { $age = 'Invalid Date'; }
            }

            // MODIFIED HELPER: Supports editing state and input generation
            $renderFields = function($fields, $cols = 'md:grid-cols-2', $isJson = false) {
                $html = "<div class='grid grid-cols-1 $cols gap-x-12 gap-y-6'>";
                foreach ($fields as $label => $value) {
                    $rawKey = strtolower(str_replace([':', ' ', "'", '#'], ['', '_', '', 'number'], $label));
                    $inputName = $isJson ? "responses[$rawKey]" : $rawKey;
                    $val = $value ?: '';

                    $html .= "
                    <div class='relative border-b-2 border-gray-200 pb-1 group hover:border-[#005288] transition-colors duration-200'>
                        <label class='block text-[10px] font-bold text-gray-400 mb-1'>$label</label>
                        
                        <p x-show='!editing' class='text-[13px] uppercase text-[#003918] min-h-6'>".($val ?: '—')."</p>
                        
                        <input x-show='editing' type='text' name='$inputName' value='$val' 
                            class='w-full text-[13px] uppercase text-[#005288] bg-white/50 border-none p-0 focus:ring-0 outline-none font-bold placeholder-gray-300'
                            placeholder='Enter $label'>
                    </div>";
                }
                $html .= "</div>";
                return $html;
            };
            @endphp

            {{-- Learner's Information --}}
            <div class="lg:col-span-2 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
                <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Learner’s Information</h2>
                {!! $renderFields([
                    'LRN:' => $student->lrn, 'Birthday:' => $student->birthday,
                    'Last Name:' => $student->last_name, 'Birthplace:' => $student->place_of_birth,
                    'First Name:' => $student->first_name, 'Age:' => $age,
                    'Middle Name:' => $student->middle_name, 'Mother Tongue:' => $student->mother_tongue,
                    'Extension Name:' => $student->extension_name, 'Sex:' => $student->sex,
                ]) !!}
            </div>

            {{-- Enrolment (Dynamic with Sections) --}}
            <div class="bg-[#F7FBF9]/60 backdrop-blur-sm rounded-3xl shadow-lg border border-green-100/50 p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="bg-[#00923F] p-1.5 rounded-lg shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <h2 class="text-[#003918] text-sm font-black uppercase tracking-widest">Enrolment</h2>
                </div>
                
                <div class="space-y-6">
                    <!-- School Year -->
                    <div class="relative border-b border-gray-100 pb-2 group transition-all duration-200">
                        <label class="block text-[10px] font-black text-gray-400 mb-1.5 tracking-[0.1em] uppercase">Academic Year</label>
                        <p x-show="!editing" class="text-[13px] uppercase text-[#003918] min-h-6 font-black tracking-tight">{{ $student->school_year ?: '—' }}</p>
                        <div x-show="editing" class="relative">
                            <select name="school_year" x-model="academic_year" @change="fetchSections()"
                                class="w-full text-[13px] uppercase text-[#005288] bg-[#F1F3F2] border-none rounded-xl py-2 px-4 focus:ring-2 focus:ring-[#00923F] outline-none font-bold appearance-none">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay }}">{{ $ay }}</option>
                                @endforeach
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#005288]/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Grade Level -->
                    <div class="relative border-b border-gray-100 pb-2 group transition-all duration-200">
                        <label class="block text-[10px] font-black text-gray-400 mb-1.5 tracking-[0.1em] uppercase">Grade Level</label>
                        <p x-show="!editing" class="text-[13px] uppercase text-[#003918] min-h-6 font-black tracking-tight">{{ $student->grade_level ?? $finalGrade }}</p>
                        <div x-show="editing" class="relative">
                            <input type="hidden" name="grade_level" :value="grade_level">
                            <select x-model="grade_level" @change="fetchSections()"
                                class="w-full text-[13px] uppercase text-[#005288] bg-[#F1F3F2] border-none rounded-xl py-2 px-4 focus:ring-2 focus:ring-[#00923F] outline-none font-bold appearance-none">
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#005288]/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Section -->
                    <div class="relative border-b border-gray-100 pb-2 group transition-all duration-200">
                        <label class="block text-[10px] font-black text-gray-400 mb-1.5 tracking-[0.1em] uppercase">Section</label>
                        <p x-show="!editing" class="text-[13px] uppercase text-[#003918] min-h-6 font-black tracking-tight">
                            {{ $student->section_name ?? '—' }}
                        </p>
                        <div x-show="editing" class="relative">
                            <input type="hidden" name="section_id" :value="section_id">
                            <select x-model="section_id"
                                class="w-full text-[13px] uppercase text-[#005288] bg-[#F1F3F2] border-none rounded-xl py-2 px-4 focus:ring-2 focus:ring-[#00923F] outline-none font-bold appearance-none">
                                <option value="">Select Section</option>
                                <template x-for="section in sections" :key="section.id">
                                    <option :value="section.id" x-text="section.name"></option>
                                </template>
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#005288]/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Data -->
                    <div class="space-y-4 pt-4 border-t border-dashed border-gray-100 mt-2">
                        <div class="relative">
                            <label class="block text-[9px] font-black text-gray-300 mb-1 uppercase">Track</label>
                            <p class="text-[12px] uppercase text-gray-400 font-bold italic">{{ $finalTrack ?: '—' }}</p>
                        </div>
                        <div class="relative">
                            <label class="block text-[9px] font-black text-gray-300 mb-1 uppercase">Cluster</label>
                            <p class="text-[12px] uppercase text-gray-400 font-bold italic">{{ $finalCluster ?: '—' }}</p>
                        </div>
                        <div class="relative">
                            <label class="block text-[9px] font-black text-gray-300 mb-1 uppercase">Academic Status</label>
                            <p class="text-[12px] uppercase text-gray-400 font-bold italic">{{ $finalStatus ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Address Section --}}
            <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-8">
                <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Current Address</h2>
                {!! $renderFields([
                    'Curr House Number:' => $student->curr_house_number, 'Curr Street:' => $student->curr_street,
                    'Curr Barangay:' => $student->curr_barangay, 'Curr City:' => $student->curr_city,
                    'Curr Province:' => $student->curr_province, 'Curr Zip Code:' => $student->curr_zip_code
                ], 'lg:grid-cols-6') !!}

                <div class="flex items-center gap-3 mt-8 mb-4">
                    <h2 class="text-[#005288] text-sm font-extrabold uppercase">Permanent Address</h2>
                    @if($student->is_perm_same_as_curr)
                        <span class="text-[9px] bg-[#f1f5fd] text-[#005288] px-2 py-0.5 rounded-full border border-[#00923F]/20 font-bold">SAME AS CURRENT</span>
                    @endif
                </div>
                {!! $renderFields([
                    'Perm House Number:' => $student->perm_house_number, 'Perm Street:' => $student->perm_street,
                    'Perm Barangay:' => $student->perm_barangay, 'Perm City:' => $student->perm_city,
                    'Perm Province:' => $student->perm_province, 'Perm Zip Code:' => $student->perm_zip_code
                ], 'lg:grid-cols-6') !!}
            </div>

            {{-- Family Information --}}
            <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7 space-y-8">
                @foreach(['Father' => 'father', 'Mother' => 'mother', 'Guardian' => 'guardian'] as $title => $key)
                    <div>
                        <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">{{ $title }}'s Information</h2>
                        {!! $renderFields([
                            "$title Last Name:" => $student->{$key.'_last_name'}, 
                            "$title First Name:" => $student->{$key.'_first_name'},
                            "$title Middle Name:" => $student->{$key.'_middle_name'}, 
                            "$title Contact Number:" => $student->{$key.'_contact_number'}
                        ], 'lg:grid-cols-4') !!}
                    </div>
                @endforeach
            </div>

            {{-- Additional Info --}}
            @if(!empty($dynamicDetails))
                @php
                    // 1. Keep the blocklist for Enrolment/Transferee
                    $alreadyRendered = [
                        'school_year', 'grade_level_to_enroll', 'track', 'cluster_of_electives', 'academic_status',
                        'last_school_year_completed', 'last_grade_level_completed', 'last_school_attended', 'school_id'
                    ];
                    
                    // 2. Add a tracker to prevent internal redundancy (Snake case vs Title case)
                    $displayedSanitizedKeys = [];
                @endphp

                <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
                    <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Additional Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-12 gap-y-6">
                        @foreach($dynamicDetails as $question => $answer)
                            @php 
                                // Sanitize the current question/key
                                $rawName = strtolower(str_replace([':', ' ', "'", '#'], ['', '_', '', 'number'], $question)); 

                                // Handle array values (e.g. multi-select checkboxes)
                                if (is_array($answer)) {
                                    $answer = implode(', ', $answer);
                                }
                            @endphp

                            {{-- Skip if it's in the Enrolment/Transferee blocklist --}}
                            @if(in_array($rawName, $alreadyRendered))
                                @continue
                            @endif

                            {{-- Skip if we have already displayed a version of this field in this loop --}}
                            @if(in_array($rawName, $displayedSanitizedKeys))
                                @continue
                            @endif

                            {{-- Record this key as "displayed" --}}
                            @php $displayedSanitizedKeys[] = $rawName; @endphp

                            <div class="relative border-b-2 border-gray-200 pb-1 group hover:border-[#005288] transition-colors duration-200">
                                <label class="block text-[10px] font-bold text-gray-400 mb-1">
                                    {{-- Make the label pretty even if the key is snake_case --}}
                                    {{ ucwords(str_replace('_', ' ', $question)) }}
                                </label>
                                
                                <p x-show="!editing" class="text-[13px] uppercase text-[#003918] min-h-6">{{ $answer ?: '—' }}</p>
                                
                                <input x-show="editing" type="text" name="responses[{{ $rawName }}]" value="{{ $answer }}" 
                                    class="w-full text-[13px] uppercase text-[#005288] bg-white border-none p-0 focus:ring-0 outline-none font-bold">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            {{-- Special Info --}}
            @php
                // Use lowercase keys since StudentController lowercases them
                $rawStatus = $details['academic status'] ?? $details['Academic Status'] ?? '';
                if (is_array($rawStatus)) {
                    $rawStatus = implode(', ', $rawStatus);
                }
                $status = trim($rawStatus);
                $isSpecialStatus = str_contains(strtolower($status), 'feree') || str_contains(strtolower($status), 'balik');
            @endphp

            @if($isSpecialStatus)
                <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
                    <h2 class="text-[#005288] text-sm font-extrabold uppercase mb-4">Transferee / Balik-Aral Information</h2>
                    {!! $renderFields([
                        'Last School Year Completed:' => $details['last school year completed'] ?? $details['Last School Year Completed'] ?? '—',
                        'Last Grade Level Completed:' => $details['last grade level completed'] ?? $details['Last Grade Level Completed'] ?? '—',
                        'Last School Attended:'       => $details['last school attended'] ?? $details['Last School Attended'] ?? '—',
                        'School ID:'                  => $details['school id'] ?? $details['School ID'] ?? '—',
                    ], 'lg:grid-cols-4', true) !!}
                </div>
            @endif
        </div>

        {{-- Sticky Bottom Save Button --}}
        <div x-show="editing" class="flex justify-end pt-6 pb-10" x-transition>
            <button type="submit" class="bg-[#00923F] hover:bg-[#007a34] text-white font-bold px-12 py-4 rounded-xl shadow-lg transition transform hover:scale-105 outline-none flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Finalize & Save Changes
            </button>
        </div>
    </form>
</div>

<!-- Documents Modal -->
<div id="docsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="toggleDocsModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full border border-gray-200">
            <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center border-b border-gray-100 pb-4 mb-4">
                    <h3 class="text-xl leading-6 font-black text-[#003918] uppercase tracking-tighter" id="modal-title">
                        Verified Documents
                    </h3>
                    <button onclick="toggleDocsModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($verifiedScans as $scan)
                        <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden shadow-sm group">
                            <div class="p-3 bg-white border-b border-gray-100 flex justify-between items-center">
                                <span class="text-xs font-bold text-[#005288] uppercase tracking-wider">{{ $scan->document_type }}</span>
                                <span class="text-[10px] text-gray-400 font-medium">{{ \Carbon\Carbon::parse($scan->created_at)->format('M d, Y') }}</span>
                            </div>
                            <div class="relative aspect-[3/4] bg-gray-200 overflow-hidden cursor-zoom-in" onclick="window.open('{{ asset('storage/' . $scan->file_path) }}', '_blank')">
                                <img src="{{ asset('storage/' . $scan->file_path) }}" alt="{{ $scan->document_type }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <span class="bg-white/90 text-[#005288] px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg">Click to Enlarge</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="toggleDocsModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-xs font-black uppercase tracking-widest text-gray-700 hover:bg-gray-100 transition-colors focus:outline-none sm:w-auto">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleDocsModal() {
        const modal = document.getElementById('docsModal');
        modal.classList.toggle('hidden');
    }
</script>
@endsection
