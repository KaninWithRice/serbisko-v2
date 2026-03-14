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
<div class="p-6 font-['Inter'] tracking-normal space-y-4">

    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-md shadow-sm mb-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <p class="text-sm font-medium">Profile data applied to the form successfully.</p>
        </div>
    </div>

    <div class="bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-5">
        <h2 class="text-[#005288] text-2xl font-extrabold uppercase tracking-tight">
            {{ $student->first_name }} {{ $student->middle_name ? substr($student->middle_name, 0, 1) . '.' : '' }} {{ $student->last_name }} {{ $student->extension_name ? $student->extension_name : '' }}
        </h2>
        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-tight">{{ $student->lrn }}</h3>
        
        <div class="mt-6 pt-4 border-t border-gray-100">
            <button type="button" class="inline-flex items-center bg-[#005288] hover:bg-[#003f66] text-white font-semibold px-5 py-2.5 rounded-lg shadow-sm transition focus:ring-2 focus:ring-offset-2 focus:ring-[#005288] outline-none">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Use Profile
            </button>
            <p class="text-xs text-gray-500 italic mt-2 ml-1">
                This will automatically fill the form using the student's saved information.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        @php
        $age = '—';
        if (!empty($student->birthday)) {
            try { $age = \Carbon\Carbon::parse($student->birthday)->age; } catch (\Exception $e) { $age = 'Invalid Date'; }
        }
        @endphp

        @php
            $renderFields = function($fields, $cols = 'md:grid-cols-2') {
                $html = "<div class='grid grid-cols-1 $cols gap-x-12 gap-y-6'>";
                foreach ($fields as $label => $value) {
                    $val = $value ?: '—';
                    $html .= "
                    <div class='relative border-b-2 border-gray-200 pb-1 group hover:border-[#005288] transition-colors duration-200'>
                        <label class='block text-[10px] font-bold text-gray-400 mb-1'>$label</label>
                        <p class='text-[13px] uppercase text-[#003918] min-h-6'>$val</p>
                    </div>";
                }
                $html .= "</div>";
                return $html;
            };
        @endphp

        <div class="lg:col-span-2 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
            <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Learner’s Information</h2>
            {!! $renderFields([
                'LRN:' => $student->lrn, 'Birthdate:' => $student->birthday??'—',
                'Last Name:' => $student->last_name??'—', 'Birthplace:' => $student->place_of_birth??'—',
                'First Name:' => $student->first_name??'—', 'Age:' => $age,
                'Middle Name:' => $student->middle_name??'—', 'Mother Tongue:' => $student->mother_tongue??'—',
                'Extension:' => $student->extension_name ?? '—', 'Gender:' => $student->sex??'—',
            ]) !!}
        </div>

        <div class="bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
            <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Enrolment</h2>
            {!! $renderFields([
                'School Year:' => $details['School Year'] ?? '—',
                'Grade Level:' => $finalGrade,    
                'Track:'       => $finalTrack,   
                'Cluster of Electives:'    => $finalCluster,  
                'Status:'      => $finalStatus    
            ], 'grid-cols-1') !!}
        </div>

        <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-8">
            <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Current Address</h2>
            {!! $renderFields([
                'House #:' => $student->curr_house_number??'—', 'Street:' => $student->curr_street??'—',
                'Barangay:' => $student->curr_barangay??'—', 'City:' => $student->curr_city??'—',
                'Province:' => $student->curr_province??'—', 'Zip:' => $student->curr_zip_code??'—'
            ], 'lg:grid-cols-6') !!}

            <div class="flex items-center gap-3 mt-8 mb-4">
                <h2 class="text-[#005288] text-sm font-extrabold uppercase">Permanent Address</h2>
                @if($student->is_perm_same_as_curr)
                    <span class="text-[9px] bg-[#f1f5fd] text-[#005288] px-2 py-0.5 rounded-full border border-[#00923F]/20 font-bold">SAME AS CURRENT</span>
                @endif
            </div>
            {!! $renderFields([
                'House #:' => $student->perm_house_number??'—', 'Street:' => $student->perm_street??'—',
                'Barangay:' => $student->perm_barangay??'—', 'City:' => $student->perm_city??'—',
                'Province:' => $student->perm_province??'—', 'Zip:' => $student->perm_zip_code??'—'
            ], 'lg:grid-cols-6') !!}
        </div>

        <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7 space-y-8">
            @foreach(['Father\'s Name' => 'father', 'Mother\'s Maiden Name' => 'mother', 'Guardian\'s Name' => 'guardian'] as $title => $key)
                <div>
                    <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">{{ $title }}</h2>
                    {!! $renderFields([
                        'Last Name:' => $student->{$key.'_last_name'}??'—', 'First Name:' => $student->{$key.'_first_name'}??'—',
                        'Middle Name:' => $student->{$key.'_middle_name'}??'—', 'Contact:' => $student->{$key.'_contact_number'}??'—'
                    ], 'lg:grid-cols-4') !!}
                </div>
            @endforeach
        </div>

        @if(!empty($dynamicDetails))
            <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
                <h2 class="text-[#005288] text-sm font-extrabold mb-4 uppercase">Additional Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-12 gap-y-6">
                    @foreach($dynamicDetails as $question => $answer)
                        <div class="relative border-b-2 border-gray-200 pb-1 group hover:border-[#005288] transition-colors duration-200">
                            <label class="block text-[10px] font-bold text-gray-400 mb-1">{{ $question }}</label>
                            <p class="text-[13px] uppercase text-[#003918] min-h-6">{{ $answer ?: '—' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            $status = trim($details['Academic Status'] ?? '');
            $isSpecialStatus = str_contains(strtolower($status), 'feree') || str_contains(strtolower($status), 'balik');
        @endphp

        @if($isSpecialStatus)
            <div class="lg:col-span-3 bg-[#F7FBF9]/40 rounded-xl shadow-md border border-gray-100 p-7">
                <div class="flex items-center gap-2 mb-4">
                    <h2 class="text-[#005288] text-sm font-extrabold uppercase">Transferee / Balik-Aral Information</h2>
                </div>
                {!! $renderFields([
                    'Last School Year Completed:' => $details['Last School Year Completed'] ?? '—',
                    'Last Grade Level Completed:' => $details['Last Grade Level Completed'] ?? '—',
                    'Last School Attended:'       => $details['Last School Attended'] ?? '—',
                    'School ID:'                  => $details['School ID'] ?? '—',
                ], 'lg:grid-cols-4') !!}
            </div>
        @endif
    </div>

    <div class="flex justify-end pt-6 pb-10">
        <button type="button" class="inline-flex items-center bg-[#00923F] hover:bg-[#007a34] text-white font-semibold px-8 py-3 rounded-lg shadow-sm transition focus:ring-2 focus:ring-offset-2 focus:ring-[#00923F] outline-none">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
            Save Changes
        </button>
    </div>

</div>
@endsection