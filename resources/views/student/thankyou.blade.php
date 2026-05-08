<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Receipt - SerbIsko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .bg-custom-gradient {
            background: radial-gradient(circle at center, #FFFFFF 10%, #E8F5E9 50%, #2e7d32 100%);
        }
        .receipt-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        .receipt-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: #00923F;
        }
        @media print {
            .no-print { display: none; }
            .print-only { display: block; }
            body { background: white; }
            .receipt-card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body class="bg-custom-gradient min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-2xl receipt-card p-8 md:p-12 mb-8">
        <div class="flex flex-col items-center mb-8">
            <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#00923F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 text-center">Enrollment Complete!</h1>
            <p class="text-gray-500 font-medium">Digital Enrollment Receipt</p>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-gray-100">
                <div>
                    <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Student Name</label>
                    <p class="text-lg font-bold text-gray-800 uppercase">
                        {{ $user->last_name }}, {{ $user->first_name }} {{ $user->middle_name }}
                    </p>
                </div>
                <div>
                    <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Learner Reference Number (LRN)</label>
                    <p class="text-lg font-bold text-gray-800">{{ $student->lrn }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-6 border-b border-gray-100">
                <div>
                    <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Grade Level</label>
                    <p class="text-base font-bold text-gray-700">Grade {{ $enrollment->grade_level }}</p>
                </div>
                <div>
                    <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Academic Status</label>
                    <p class="text-base font-bold text-gray-700 uppercase">{{ str_replace('_', ' ', $enrollment->academic_status) }}</p>
                </div>
                <div>
                    <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Track</label>
                    <p class="text-base font-bold text-gray-700 uppercase">{{ $enrollment->track }}</p>
                </div>
            </div>

            <div class="pb-6 border-b border-gray-100">
                <label class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Selected Cluster</label>
                <p class="text-base font-bold text-gray-700 uppercase">{{ $enrollment->cluster }}</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6 flex flex-col items-center justify-center border-2 border-dashed border-gray-200">
                <label class="text-[10px] uppercase tracking-widest text-gray-500 font-extrabold mb-1">Receipt Number</label>
                <p class="text-3xl font-black text-[#005288] tracking-tighter">{{ $enrollment->receipt_number }}</p>
                <p class="text-[11px] text-gray-400 mt-2 font-medium">Generated on {{ \Carbon\Carbon::parse($enrollment->completed_at)->format('F d, Y h:i A') }}</p>
            </div>
        </div>

        <div class="mt-8 text-center space-y-4">
            <p class="text-[#b91c1c] font-bold text-sm animate-pulse">
                📸 Please take a picture or write down your receipt number.
            </p>
            
            <div class="flex flex-col items-center gap-2">
                <button type="button" onclick="openEmailModal()" class="no-print w-full py-4 bg-[#005288] hover:bg-[#003d66] text-white rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center gap-2">
                    SEND TO E-MAIL
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                </button>

                <a href="/logout" class="no-print w-full py-4 bg-[#00923F] hover:bg-[#007a35] text-white rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center gap-2">
                    DONE
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
                
                <p class="text-gray-400 text-[11px] font-medium">
                    Redirecting to login in <span id="timer">180</span> seconds...
                </p>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden no-print">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-extrabold text-gray-900">Send Digital Receipt</h3>
                <button onclick="closeEmailModal()" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <p class="text-gray-600 mb-6 text-sm">
                Enter your email address below to receive a digital copy of your enrollment receipt.
            </p>

            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Email Address</label>
                    <input type="email" id="emailInput" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#00923F] focus:ring-2 focus:ring-[#00923F]/20 outline-none transition-all" placeholder="your@email.com">
                </div>
                
                <button id="sendEmailBtn" onclick="sendEmail()" class="w-full py-4 bg-[#00923F] hover:bg-[#007a35] text-white rounded-xl font-bold text-lg transition-all flex items-center justify-center gap-2">
                    <span>SEND RECEIPT</span>
                    <div id="loadingSpinner" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </div>
            
            <div id="emailMessage" class="mt-4 text-sm text-center hidden"></div>
        </div>
    </div>

    <p class="text-white/70 text-sm font-medium no-print">
        Welcome to TNCHS-SHS, Ka-Compre!
    </p>

    <script>
        let seconds = 180;
        const timerElement = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            seconds--;
            timerElement.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = '/logout';
            }
        }, 1000);

        function openEmailModal() {
            document.getElementById('emailModal').classList.remove('hidden');
            document.getElementById('emailInput').focus();
        }

        function closeEmailModal() {
            document.getElementById('emailModal').classList.add('hidden');
        }

        async function sendEmail() {
            const email = document.getElementById('emailInput').value;
            const btn = document.getElementById('sendEmailBtn');
            const spinner = document.getElementById('loadingSpinner');
            const message = document.getElementById('emailMessage');
            
            if (!email || !email.includes('@')) {
                message.textContent = "Please enter a valid email address.";
                message.className = "mt-4 text-sm text-center text-red-600";
                message.classList.remove('hidden');
                return;
            }

            // Disable button and show spinner
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            spinner.classList.remove('hidden');
            message.classList.add('hidden');

            try {
                const response = await fetch('/student/send-receipt-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ email: email })
                });

                const result = await response.json();

                if (result.success) {
                    message.textContent = result.message;
                    message.className = "mt-4 text-sm text-center text-green-600 font-bold";
                    setTimeout(() => closeEmailModal(), 2000);
                } else {
                    message.textContent = result.message;
                    message.className = "mt-4 text-sm text-center text-red-600";
                }
            } catch (error) {
                message.textContent = "An error occurred. Please try again.";
                message.className = "mt-4 text-sm text-center text-red-600";
            } finally {
                message.classList.remove('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                spinner.classList.add('hidden');
            }
        }
    </script>
</body>
</html>