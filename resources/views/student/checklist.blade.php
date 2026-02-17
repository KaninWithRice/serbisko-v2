<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - Requirements Checklist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .bg-custom-gradient {
            background: linear-gradient(180deg, #FFFFFF 0%, #E8F5E9 40%, #1b5e20 100%);
        }
    </style>
</head>
<body class="bg-custom-gradient min-h-screen flex flex-col items-center justify-center p-4">

    <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-blue-900 flex items-center justify-center gap-2 mb-2">
            Required Documents Checklist <span class="text-3xl">📋</span>
        </h1>
        <p class="text-gray-600">Select all original documents you have prepared for submission.</p>
    </div>

    <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl p-8 md:p-10 border border-gray-100">
        
        <h2 class="text-xl font-bold text-blue-900 mb-6">Document Requirements:</h2>

        <form id="checklistForm" action="{{ url('/student/save-checklist') }}" method="POST">
            @csrf

            <div id="error-msg" class="hidden mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm font-bold text-center">
                ⚠️ Please confirm you have ALL the required documents by checking the boxes.
            </div>

            <div class="space-y-4 mb-8">
                {{-- DYNAMIC LOGIC: Define list based on session status --}}
                @php
                    $status = session('student_status', 'regular'); // default to regular if empty
                    
                    if ($status === 'als') {
                        $documents = [
                            'ALS Certificate of Rating',
                            'Enrollment Form',
                            'PSA Birth Certificate',
                            'Affidavit of Undertaking'
                        ];
                    } elseif ($status === 'transferee' || $status === 'balik_aral') {
                        $documents = [
                            'Report Card (SF9)',
                            'PSA Birth Certificate',
                            'Affidavit of Undertaking', // Required for these types
                            'Enrollment Form'
                        ];
                    } else {
                        // Regular Student
                        $documents = [
                            'Report Card (SF9)',
                            'PSA Birth Certificate',
                            'Enrollment Form'
                        ];
                    }
                @endphp

                {{-- LOOP through documents --}}
                @foreach($documents as $doc)
                <label class="flex items-start gap-4 cursor-pointer group p-3 rounded-lg hover:bg-gray-50 transition border border-transparent hover:border-gray-200">
                    <div class="relative flex items-center">
                        <input type="checkbox" name="documents[]" value="{{ $doc }}" class="peer h-6 w-6 cursor-pointer appearance-none rounded-md border-2 border-green-800 transition-all checked:bg-green-800 checked:border-green-800">
                        <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <span class="text-gray-700 font-medium text-lg select-none group-hover:text-green-900">
                        {{ $doc }}
                    </span>
                </label>
                @endforeach
            </div>

            <div class="flex flex-col md:flex-row justify-center gap-4 mt-8">
                <a href="{{ url('/student/cluster-selection') }}" 
                   class="px-10 py-3 rounded-full border-2 border-blue-900 text-blue-900 font-bold hover:bg-blue-50 transition text-center">
                    BACK
                </a>

                <button type="button" onclick="validateChecklist()" 
                        class="px-10 py-3 rounded-full bg-blue-900 text-white font-bold hover:bg-blue-800 shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                    PROCEED TO SUBMISSION
                </button>
            </div>

        </form>
    </div>

    <script>
        function validateChecklist() {
            // Get all checkboxes
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            let allChecked = true;

            // Check if every single one is checked
            checkboxes.forEach((box) => {
                if (!box.checked) {
                    allChecked = false;
                }
            });

            if (allChecked) {
                // If all good, submit!
                document.getElementById('checklistForm').submit();
            } else {
                // Show error
                document.getElementById('error-msg').classList.remove('hidden');
                // Scroll to top of card to see error
                document.getElementById('checklistForm').scrollIntoView({behavior: 'smooth'});
            }
        }
    </script>

</body>
</html>