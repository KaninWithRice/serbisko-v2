<div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
    
    <div class="bg-[#F7FBF9]/50 rounded-2xl shadow-lg border-t-8 border-[#1a8a44] p-4 flex flex-col justify-between">
        <div>
            <h3 class="text-xl font-black text-[#003918] uppercase tracking-tighter">Total Registrations</h3>
            <p class="text-gray-500 text-[10px] italic mb-2">Students who successfully completed and submitted the Google Form</p>
            <div class="text-3xl font-medium text-[#0c4222] mb-2">
                {{ number_format($totalRegistrations) }}
            </div>
        </div>
    </div>

    <div class="bg-[#F7FBF9]/50 rounded-2xl shadow-lg border-t-8 border-[#1a8a44] p-4 flex flex-col justify-between">
        <div>
            <h3 class="text-xl font-black text-[#003918] uppercase tracking-tighter">Total Submissions Received</h3>
            <p class="text-gray-500 text-[10px] italic mb-2">Applicants who have submitted required documents through the Serbisko Kiosk</p>
            <div class="text-3xl font-medium text-[#0c4222] mb-2">
                {{ number_format($totalSubmissions) }}
            </div>
        </div>
        <a href="{{ route('admin.students', ['status' => 'For Enrollment', 'grade_level' => request('grade_level')]) }}" 
        class="text-[#00568d] font-bold underline text-md hover:text-[#005288]/50 transition-colors inline-block mt-auto">
            View
        </a>
    </div>

    <div class="bg-[#F7FBF9]/50 rounded-2xl shadow-lg border-t-8 border-[#1a8a44] p-4 flex flex-col justify-between">
        <div>
            <h3 class="text-xl font-black text-[#003918] uppercase tracking-tighter">Total Enrolled Students</h3>
            <p class="text-gray-500 text-[10px] italic mb-2">Students who are successfully enrolled in DepEd LIS</p>
            <div class="text-3xl font-medium text-[#0c4222] mb-2">
                {{ number_format($totalEnrolled) }}
            </div>
        </div>
        <a href="{{ route('admin.students', ['status' => 'Enrolled']) }}" 
           class="text-[#00568d] font-bold underline text-md hover:text-[#005288]/50 transition-colors inline-block mt-auto">
            View
        </a>
    </div>
</div>