@extends('admin.layout')

@section('page_title')
    <div class="flex justify-center items-end w-full pb-2 font-['Inter'] tracking-normal">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-baseline space-x-2">
                <li><a href="{{ route('admin.students') }}" class="text-[16px] font-medium text-gray-500 hover:text-[#00923F] transition-colors">Students</a></li>
                <li class="flex text-[16px] font-bold text-[#00923F]">
                    <span class="mx-2 text-gray-400 select-none">></span>
                    <span>{{ $student->first_name }} {{ $student->last_name }} {{ $student->extension_name ? $student->extension_name : '' }}'s Profile</span>
                </li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
{{-- Wrap the entire section in Alpine.js and a Form --}}
<div x-data="{ editing: false }" class="p-6 font-['Inter'] tracking-normal space-y-4">

    @php
        $renderFields = function($fields, $cols = 'md:grid-cols-2', $isJson = false) {
            $html = "<div class='grid grid-cols-1 $cols gap-x-12 gap-y-6'>";
            foreach ($fields as $label => $value) {
                // Logic to create input names...
                $rawKey = strtolower(str_replace([':', ' ', "'", '#'], ['', '_', '', 'number'], $label));
                $inputName = $isJson ? "responses[$rawKey]" : $rawKey;
                $val = $value ?: '';

                $html .= "
                <div class='relative border-b-2 border-gray-200 pb-1 group hover:border-[#005288] transition-colors duration-200'>
                    <label class='block text-[10px] font-bold text-gray-400 mb-1'>$label</label>
                    <p x-show='!editing' class='text-[13px] uppercase text-[#003918] min-h-6'>".($val ?: '—')."</p>
                    <input x-show='editing' type='text' name='$inputName' value='$val' 
                        class='w-full text-[13px] uppercase text-[#005288] bg-white border-none p-0 focus:ring-0 outline-none font-bold'>
                </div>";
            }
            $html .= "</div>";
            return $html;
        };
        
        // You can also calculate the age here since it's used later
        $age = !empty($student->birthday) ? \Carbon\Carbon::parse($student->birthday)->age : '—';
    @endphp
    
    <form action="{{ route('admin.students.update', $student->id) }}" method="POST">
        @csrf
        @method('PUT')

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-md shadow-sm mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-5 flex justify-between items-center">
            <div>
                <h2 class="text-[#005288] text-2xl font-extrabold uppercase tracking-tight flex items-center gap-3">
                    {{ $student->first_name }} {{ $student->middle_name ? substr($student->middle_name, 0, 1) . '.' : '' }} {{ $student->last_name }} {{ $student->extension_name ? $student->extension_name : '' }}
                    
                    @if($student->is_manually_edited)
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

            {{-- Enrolment (Read Only or Sync from JSON) --}}
            <div class="bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
                <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Enrolment</h2>
                {!! $renderFields([
                    'School Year:' => $details['School Year'] ?? '—',
                    'Grade Level to Enroll:' => $finalGrade,    
                    'Track:'       => $finalTrack,   
                    'Cluster of Electives:'    => $finalCluster,  
                    'Academic Status:'      => $finalStatus    
                ], 'grid-cols-1', true) !!}
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

                            <div class="relative border-b-2 border-gray-200 pb-1 ...">
                                <label class="block text-[10px] font-bold text-gray-400 mb-1">
                                    {{-- Make the label pretty even if the key is snake_case --}}
                                    {{ ucwords(str_replace('_', ' ', $question)) }}
                                </label>
                                
                                <p x-show="!editing" class="text-[13px] uppercase text-[#003918] min-h-6">{{ $answer ?: '—' }}</p>
                                
                                <input x-show="editing" type="text" name="responses[{{ $rawName }}]" value="{{ $answer }}" 
                                    class="w-full text-[13px] uppercase text-[#005288] ...">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            {{-- Special Info --}}
            @php
                $status = trim($details['Academic Status'] ?? '');
                $isSpecialStatus = str_contains(strtolower($status), 'feree') || str_contains(strtolower($status), 'balik');
            @endphp

            @if($isSpecialStatus)
                <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
                    <h2 class="text-[#005288] text-sm font-extrabold uppercase mb-4">Transferee / Balik-Aral Information</h2>
                    {!! $renderFields([
                        'Last School Year Completed:' => $details['Last School Year Completed'] ?? '—',
                        'Last Grade Level Completed:' => $details['Last Grade Level Completed'] ?? '—',
                        'Last School Attended:'       => $details['Last School Attended'] ?? '—',
                        'School ID:'                  => $details['School ID'] ?? '—',
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