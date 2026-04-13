<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>SerbIsko - Capture Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .bg-custom-gradient {
            background: linear-gradient(180deg, #FFFFFF 0%, #E8F5E9 40%, #1b5e20 100%);
        }
        @keyframes scan-move {
            0%, 100% { transform: translateY(0); opacity: 0.5; }
            50% { transform: translateY(10px); opacity: 1; }
        }
        .animate-scan {
            animation: scan-move 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-custom-gradient min-h-screen flex flex-col items-center justify-center p-4 relative">

    <div class="absolute top-4 left-4 flex flex-col gap-2 z-50">
        <div id="status-ocr" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 shadow-sm border border-gray-200">
            <span class="indicator-dot w-2 h-2 bg-gray-400 rounded-full"></span>
            <span class="indicator-text">OCR Engine: Checking...</span>
        </div>
        <div id="status-lis" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 shadow-sm border border-gray-200">
            <span class="indicator-dot w-2 h-2 bg-gray-400 rounded-full"></span>
            <span class="indicator-text">LIS Verifier: Checking...</span>
        </div>
        <div id="status-arduino" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 shadow-sm border border-gray-200">
            <span class="indicator-dot w-2 h-2 bg-gray-400 rounded-full"></span>
            <span class="indicator-text">Arduino Link: Checking...</span>
        </div>
    </div>

    <div id="loading-popup" class="fixed inset-0 bg-black/80 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-gradient-to-b from-green-600 via-green-100 to-white p-1 rounded-[2.5rem] shadow-2xl w-full max-w-sm">
            <div class="bg-white rounded-[2.4rem] p-8 text-center">
                <div class="flex justify-center mb-6 relative h-24 w-24 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-full w-full text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-12 h-0.5 bg-green-500 animate-scan"></div>
                </div>
                <h3 class="text-xl font-bold text-blue-900 leading-tight mb-3">Running Document Type Recognition...</h3>
                <p class="text-gray-600 font-medium">Please wait.</p>
            </div>
        </div>
    </div>

    <div id="error-popup" class="fixed inset-0 bg-black/80 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-gradient-to-b from-green-700 via-green-100 to-white p-1 rounded-[2.5rem] shadow-2xl w-full max-w-sm transform transition-all scale-100">
            <div class="bg-white rounded-[2.4rem] p-8 text-center">
                <div class="flex justify-center mb-4 relative h-24 w-24 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-full w-full text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-red-600 leading-tight mb-2">Document Not Recognized</h3>
                <p id="attempt-counter" class="text-blue-900 font-bold text-sm mb-1"></p>
                <p id="error-message" class="text-gray-700 text-sm font-medium mb-6">
                    Make sure the correct original document is placed flat on the scanner.
                </p>
                <button type="button" id="rescan-btn" class="bg-blue-900 text-white text-sm font-bold py-3 px-8 rounded-full shadow-md hover:bg-blue-800 transition tracking-wide w-full">
                    RE-SCAN DOCUMENT
                </button>
            </div>
        </div>
    </div>
    
    <div class="text-center mb-8 max-w-2xl mt-24 lg:mt-0">
        <h1 class="text-3xl md:text-4xl font-bold text-green-900 mb-2">
            Capture your <span class="text-blue-900">{{ session('current_doc', 'Report Card') }}</span>
        </h1>
        <p class="text-gray-600 text-sm md:text-base">
            We will analyze the document. If applicable, we will verify details with DepEd LIS.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full max-w-6xl items-center">
        <div class="bg-white p-8 rounded-3xl shadow-lg border border-gray-100 h-full flex flex-col justify-center">
            <h3 class="text-xl font-bold text-gray-900 mb-6">Instructions:</h3>
            <ul class="space-y-6">
                <li class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-green-900 text-white flex items-center justify-center font-bold flex-shrink-0">1</div>
                    <p class="text-gray-600 mt-1">Position your document within the camera view.</p>
                </li>
                <li class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-green-900 text-white flex items-center justify-center font-bold flex-shrink-0">2</div>
                    <p class="text-gray-600 mt-1">Ensure adequate lighting and hold steady.</p>
                </li>
                <li class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-full bg-green-900 text-white flex items-center justify-center font-bold flex-shrink-0">3</div>
                    <p class="text-gray-600 mt-1">Click "CAPTURE" to automatically analyze.</p>
                </li>
            </ul>
        </div>

        <div class="relative">
            <div class="bg-black rounded-3xl overflow-hidden shadow-2xl border-4 border-blue-900 aspect-video relative group">
                <video id="camera-stream" autoplay playsinline class="w-full h-full object-contain"></video>
                <canvas id="capture-canvas" class="hidden w-full h-full object-contain"></canvas>
                <div id="camera-message" class="absolute inset-0 flex items-center justify-center text-white text-center p-4 bg-black/50 z-10">
                    <p class="font-bold flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Starting High-Res Camera...
                    </p>
                </div>
            </div>

            <div class="mt-8 flex justify-center gap-6">
                <a id="back-link" href="{{ url('/student/checklist') }}" class="bg-white border-2 border-blue-900 text-blue-900 font-bold text-lg px-12 py-4 rounded-full hover:bg-gray-50 transition transform hover:scale-105 tracking-wide">
                    BACK
                </a>
                <form id="uploadForm" action="{{ url('/student/save-image') }}" method="POST">
                    @csrf 
                    <input type="hidden" name="image_data" id="image-data">
                    <input type="hidden" name="document_type" value="{{ session('current_doc', 'Report Card') }}">

                    <button type="button" id="capture-btn" class="bg-blue-900 text-white text-lg font-bold py-4 px-16 rounded-full shadow-lg hover:bg-blue-800 transition transform hover:scale-105 tracking-wide disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        CAPTURE
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // 0. HANDLE BACK BUTTON - CLOSE DOOR
        const backLink = document.getElementById('back-link');
        if (backLink) {
            backLink.addEventListener('click', async (e) => {
                e.preventDefault();
                const targetUrl = backLink.href;
                
                // Show a brief "Closing..." status if you want, but immediate redirect is better
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 1500); // 1.5s timeout
                    
                    await fetch('http://127.0.0.1:51234/api/door/close', { 
                        method: 'POST',
                        signal: controller.signal,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'close' })
                    });
                    clearTimeout(timeoutId);
                } catch (err) {
                    console.error("Hardware Close Error:", err);
                } finally {
                    window.location.href = targetUrl;
                }
            });
        }

        // 1. SERVER PINGING LOGIC (RUNS ONCE)
        const SERVERS = {
            ocr: { url: 'http://127.0.0.1:9001/status', method: 'GET', label: 'OCR Engine' },
            lis: { url: 'http://127.0.0.1:5001/status', method: 'GET', label: 'LIS Verifier' },
            arduino: { url: 'http://127.0.0.1:51234/status', method: 'GET', label: 'Arduino Link' }
        };

        function updateBadge(id, isOnline, label) {
            const badge = document.getElementById(`status-${id}`);
            const dot = badge.querySelector('.indicator-dot');
            const text = badge.querySelector('.indicator-text');
            text.textContent = `${label}: ${isOnline ? 'Online' : 'Offline'}`;
            if (isOnline) {
                badge.className = "bg-green-100 text-green-700 border-green-200 px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 shadow-sm border transition-colors duration-300";
                dot.className = "indicator-dot w-2 h-2 bg-green-500 rounded-full animate-pulse";
            } else {
                badge.className = "bg-red-100 text-red-700 border-red-200 px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 shadow-sm border transition-colors duration-300";
                dot.className = "indicator-dot w-2 h-2 bg-red-600 rounded-full";
            }
        }

        async function pingServer(id, config) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 2000);
                const response = await fetch(config.url, { method: config.method, signal: controller.signal });
                clearTimeout(timeoutId);
                updateBadge(id, response.ok, config.label);
            } catch (error) { updateBadge(id, false, config.label); }
        }

        function checkAllServers() {
            pingServer('ocr', SERVERS.ocr);
            pingServer('lis', SERVERS.lis);
            pingServer('arduino', SERVERS.arduino);
        }
        checkAllServers();

        // 2. ULTRA HIGH RESOLUTION CAMERA LOGIC
        const video = document.getElementById('camera-stream');
        const canvas = document.getElementById('capture-canvas');
        const message = document.getElementById('camera-message');
        const captureBtn = document.getElementById('capture-btn');
        const imageDataInput = document.getElementById('image-data');
        const uploadForm = document.getElementById('uploadForm');
        
        const loadingPopup = document.getElementById('loading-popup');
        const errorPopup = document.getElementById('error-popup');
        const rescanBtn = document.getElementById('rescan-btn');
        const errorMessageLabel = document.getElementById('error-message');
        const attemptCounterLabel = document.getElementById('attempt-counter');

        async function startCamera() {
            try {
                // Requesting high resolution with fallback
                const constraints = {
                    video: {
                        width: { ideal: 1920, max: 4096 },
                        height: { ideal: 1080, max: 2160 },
                        facingMode: { ideal: "environment" },
                        advanced: [{ zoom: 1 }]
                    }
                };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = stream;
                
                video.onloadedmetadata = () => {
                    message.classList.add('hidden');
                    captureBtn.disabled = false;
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                };
            } catch (err) {
                console.error("Camera Error:", err);
                message.innerHTML = "<span class='text-red-400'>⚠️ Camera access denied or failed. Please check permissions.</span>";
            }
        }

        startCamera();

        // 3. AJAX CAPTURE & FETCH LOGIC
        captureBtn.addEventListener('click', async () => {
            // Show loading popup
            loadingPopup.classList.remove('hidden');
            captureBtn.disabled = true; 

            await new Promise(resolve => setTimeout(resolve, 50));

            const context = canvas.getContext('2d');
            // UPDATED: Removed context.translate and context.scale so it saves exactly what the camera sees
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert to max quality 1.0 JPEG for OCR accuracy
            const dataURL = canvas.toDataURL('image/jpeg', 1.0); 
            imageDataInput.value = dataURL;

            // Send via fetch API to read Python response without reloading page
            try {
                const formData = new FormData(uploadForm);
                
                // Increase browser timeout for this specific heavy request (5 Minutes)
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 300000); // 5 minute timeout

                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                clearTimeout(timeoutId);

                // If Laravel redirects us on success (e.g., to /student/verifying)
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }

                // If Laravel returns JSON
                const result = await response.json();

                if (!response.ok || result.status === 'error' || result.status === 'failed') {
                    // PYTHON FAILED: Hide loader, show ERROR popup
                    loadingPopup.classList.add('hidden');
                    errorPopup.classList.remove('hidden');
                    if(result.message) {
                        errorMessageLabel.textContent = result.message;
                    }
                    if(result.attempts) {
                        attemptCounterLabel.textContent = `Attempt ${result.attempts} of 3`;
                    }
                } else if (result.status === 'success' && result.redirect) {
                    // SUCCESS! Force redirect to verify screen
                    window.location.href = result.redirect;
                }

            } catch (error) {
                console.error("Upload Error:", error);
                loadingPopup.classList.add('hidden');
                errorPopup.classList.remove('hidden');
                
                if (error.name === 'AbortError') {
                    errorMessageLabel.textContent = "Request timed out. The document is too complex or the connection is slow. Please try again.";
                } else {
                    errorMessageLabel.textContent = "Server connection failed. Please ensure the AI Engine is online.";
                }
            }
        });

        // 4. RESCAN BUTTON LOGIC
        rescanBtn.addEventListener('click', () => {
            // Hide error popup and re-enable capture
            errorPopup.classList.add('hidden');
            captureBtn.disabled = false;
            imageDataInput.value = ''; // clear old image
        });
    </script>
</body>
</html>