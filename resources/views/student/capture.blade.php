<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - Capture Document</title>
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

    <div class="absolute top-4 left-4 bg-red-100 text-red-600 px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 shadow-sm">
        <span class="w-2 h-2 bg-red-600 rounded-full animate-pulse"></span>
        LIS Server: Offline
    </div>

    <div class="text-center mb-8 max-w-2xl">
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
                    <p class="text-gray-600 mt-1">Click "CAPTURE" when ready.</p>
                </li>
            </ul>
        </div>

        <div class="relative">
            <div class="bg-black rounded-3xl overflow-hidden shadow-2xl border-4 border-blue-900 aspect-video relative group">
                
                <video id="camera-stream" autoplay playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
                
                <canvas id="capture-canvas" class="hidden w-full h-full object-cover"></canvas>

                <div id="camera-message" class="absolute inset-0 flex items-center justify-center text-white text-center p-4 bg-black/50">
                    <p>Requesting camera access...</p>
                </div>
            </div>

            <div class="mt-8 flex justify-center">
                <form id="uploadForm" action="{{ url('/student/save-image') }}" method="POST">
                    @csrf
                    <input type="hidden" name="image_data" id="image-data">
                    <input type="hidden" name="document_type" value="{{ session('current_doc', 'Report Card') }}">

                    <button type="button" id="capture-btn" class="bg-blue-900 text-white text-lg font-bold py-4 px-16 rounded-full shadow-lg hover:bg-blue-800 transition transform hover:scale-105 tracking-wide">
                        CAPTURE
                    </button>
                    
                    <div id="action-buttons" class="hidden gap-4">
                        <button type="button" id="retake-btn" class="bg-gray-200 text-gray-800 font-bold py-3 px-8 rounded-full hover:bg-gray-300">
                            RETAKE
                        </button>
                        <button type="submit" class="bg-green-700 text-white font-bold py-3 px-8 rounded-full hover:bg-green-800 shadow-lg">
                            SUBMIT PHOTO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const video = document.getElementById('camera-stream');
        const canvas = document.getElementById('capture-canvas');
        const message = document.getElementById('camera-message');
        const captureBtn = document.getElementById('capture-btn');
        const actionButtons = document.getElementById('action-buttons');
        const retakeBtn = document.getElementById('retake-btn');
        const imageDataInput = document.getElementById('image-data');

        // 1. Start Camera on Load
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                message.classList.add('hidden'); // Hide "Requesting..." text
            } catch (err) {
                console.error("Error accessing camera: ", err);
                message.textContent = "⚠️ Camera access denied or not available.";
                message.classList.remove('hidden');
            }
        }

        startCamera();

        // 2. Capture Image Logic
        captureBtn.addEventListener('click', () => {
            // Draw video frame to canvas
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            // Convert to Base64 string for form submission
            const dataURL = canvas.toDataURL('image/jpeg');
            imageDataInput.value = dataURL;

            // UI Toggle
            video.classList.add('hidden');
            canvas.classList.remove('hidden');
            captureBtn.classList.add('hidden');
            actionButtons.classList.remove('hidden');
            actionButtons.classList.add('flex');
        });

        // 3. Retake Logic
        retakeBtn.addEventListener('click', () => {
            video.classList.remove('hidden');
            canvas.classList.add('hidden');
            captureBtn.classList.remove('hidden');
            actionButtons.classList.add('hidden');
            actionButtons.classList.remove('flex');
        });
    </script>
</body>
</html>