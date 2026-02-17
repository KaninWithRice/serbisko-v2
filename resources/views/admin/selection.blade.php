<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - Grade Selection</title>
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
        <h1 class="text-4xl md:text-5xl font-bold text-blue-900 flex items-center justify-center gap-2">
            Hi, {{ session('user_name') }}! <span class="text-4xl">👋</span>
        </h1>
    </div>

    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-green-900 mb-2">
            What grade level are you enrolling in?
        </h2>
        <p class="text-gray-600 text-lg">
            Please choose either Grade 11 or Grade 12 to continue.
        </p>
    </div>

    <form action="#" method="POST" class="w-full max-w-4xl">
        <div class="flex flex-col md:flex-row justify-center gap-8 mb-12">
            
            <label class="cursor-pointer group">
                <input type="radio" name="grade_level" value="11" class="peer sr-only">
                <div class="w-64 h-64 bg-gray-100 rounded-3xl border-4 border-transparent peer-checked:border-blue-900 peer-checked:bg-white shadow-lg flex items-center justify-center transition-all transform hover:-translate-y-2">
                    <span class="text-3xl font-bold text-gray-400 peer-checked:text-blue-900 group-hover:text-gray-600">
                        Grade 11
                    </span>
                </div>
            </label>

            <label class="cursor-pointer group">
                <input type="radio" name="grade_level" value="12" class="peer sr-only">
                <div class="w-64 h-64 bg-gray-100 rounded-3xl border-4 border-transparent peer-checked:border-blue-900 peer-checked:bg-white shadow-lg flex items-center justify-center transition-all transform hover:-translate-y-2">
                    <span class="text-3xl font-bold text-gray-400 peer-checked:text-blue-900 group-hover:text-gray-600">
                        Grade 12
                    </span>
                </div>
            </label>

        </div>

        <div class="flex justify-center">
            <button type="button" class="bg-blue-900 text-white font-bold text-lg px-16 py-4 rounded-full shadow-lg hover:bg-blue-800 transition transform hover:scale-105">
                NEXT
            </button>
        </div>
    </form>

</body>
</html>