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
        <p class="text-gray-600">Select the documents you have ready for scanning now.</p>
    </div>

    <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl p-8 md:p-10 border border-gray-100">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-blue-900">Document Requirements:</h2>
            <a href="{{ url('/logout') }}" class="text-red-600 text-sm font-bold hover:underline">Logout</a>
        </div>

        <form id="checklistForm" action="{{ url('/student/save-checklist') }}" method="POST">
            @csrf

            <div id="error-msg" class="hidden mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm font-bold text-center">
                ⚠️ Please select at least one document you have prepared.
            </div>

            <div class="space-y-4 mb-8">
                @foreach($requiredDocs as $label => $prefix)
                    @php
                        $statusCol = $prefix . '_status';
                        $status = $enrollment->$statusCol ?? 'pending';
                        
                        // SPECIAL CASE: SF9 consolidated check
                        $isAlreadySubmitted = ($status === 'verified' || $status === 'manual_verification');
                        
                        if ($prefix === 'sf9' && $isAlreadySubmitted) {
                            // Also check the back side
                            $backStatus = $enrollment->sf9_back_status ?? 'pending';
                            if ($backStatus !== 'verified' && $backStatus !== 'manual_verification') {
                                $isAlreadySubmitted = false;
                            }
                        }
                    @endphp

                    <label class="flex items-start gap-4 cursor-pointer group p-3 rounded-lg transition border {{ $isAlreadySubmitted ? 'bg-green-50 border-green-200' : 'hover:bg-gray-50 border-transparent hover:border-gray-200' }}">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="documents[]" value="{{ $label }}" 
                                   {{ $isAlreadySubmitted ? 'checked onclick="return false;"' : '' }}
                                   class="peer h-6 w-6 cursor-pointer appearance-none rounded-md border-2 {{ $isAlreadySubmitted ? 'bg-green-600 border-green-600' : 'border-green-800 checked:bg-green-800 checked:border-green-800' }} transition-all">
                            <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 text-white {{ $isAlreadySubmitted ? 'opacity-100' : 'opacity-0 peer-checked:opacity-100' }} pointer-events-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-700 font-medium text-lg select-none group-hover:text-green-900 {{ $isAlreadySubmitted ? 'text-green-800' : '' }}">
                                {{ $label }}
                            </span>
                            @if($isAlreadySubmitted)
                                <span class="text-xs font-bold text-green-600 uppercase tracking-widest">
                                    {{ $status === 'verified' ? '✓ Submitted & Verified' : '⌚ Pending Admin Review' }}
                                </span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex flex-col md:flex-row justify-center gap-4 mt-8">
                @if(!$enrollment->cluster)
                <a href="{{ url('/student/cluster-selection') }}" 
                   class="px-10 py-3 rounded-full border-2 border-blue-900 text-blue-900 font-bold hover:bg-blue-50 transition text-center">
                    BACK
                </a>
                @endif

                <button type="button" onclick="validateChecklist()" 
                        class="px-10 py-3 rounded-full bg-blue-900 text-white font-bold hover:bg-blue-800 shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                    PROCEED TO SCANNING
                </button>
            </div>

        </form>
    </div>

    <script>
        function validateChecklist() {
            // Get all checkboxes that are checked AND NOT already submitted (we only want to scan NEW ones)
            const checkboxes = document.querySelectorAll('input[name="documents[]"]:checked');
            
            // For the submission, we actually only want to process the ones that are NOT already submitted
            // However, the current logic in saveChecklist just takes whatever is in the POST.
            // Let's filter in JS or just let PHP handle it.
            
            let newDocsToScan = false;
            checkboxes.forEach(cb => {
                if (!cb.hasAttribute('onclick')) { // Our hack for "already submitted"
                    newDocsToScan = true;
                }
            });

            if (newDocsToScan) {
                document.getElementById('checklistForm').submit();
            } else {
                document.getElementById('error-msg').innerText = "⚠️ Please select at least one NEW document to scan.";
                document.getElementById('error-msg').classList.remove('hidden');
                document.getElementById('checklistForm').scrollIntoView({behavior: 'smooth'});
            }
        }
    </script>

</body>
</html>
