<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - Choose Track</title>
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

    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-green-900 mb-4">
            Which <span class="text-blue-900">Track</span> are you taking?
        </h1>
        <p class="text-gray-600 text-lg">Select your preferred path for the new SHS Curriculum.</p>
    </div>

    <form action="{{ url('/student/save-track') }}" method="POST" class="w-full max-w-4xl">
        @csrf
        <div class="flex flex-col md:flex-row justify-center gap-8 mb-12">
            
            <label class="cursor-pointer group flex-1">
                <input type="radio" name="track" value="academic" class="peer sr-only" required>
                <div class="h-64 bg-white rounded-3xl shadow-md border-4 border-transparent peer-checked:border-blue-900 flex flex-col items-center justify-center p-6 transition-all transform hover:-translate-y-2">
                    <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mb-4">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-gray-800">Academic Track</span>
                </div>
            </label>

            <label class="cursor-pointer group flex-1">
                <input type="radio" name="track" value="techpro" class="peer sr-only">
                <div class="h-64 bg-white rounded-3xl shadow-md border-4 border-transparent peer-checked:border-blue-900 flex flex-col items-center justify-center p-6 transition-all transform hover:-translate-y-2">
                    <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-gray-800">TechPro Track</span>
                </div>
            </label>

        </div>

        <div class="flex justify-center gap-6">
            <a href="{{ url('/student/status-selection') }}" class="bg-white border-2 border-blue-900 text-blue-900 font-bold text-lg px-12 py-3 rounded-full hover:bg-gray-50 transition">BACK</a>
            <button type="submit" class="bg-blue-900 text-white font-bold text-lg px-12 py-3 rounded-full shadow-lg hover:bg-blue-800 transition">NEXT</button>
        </div>
    </form>
</body>
</html>