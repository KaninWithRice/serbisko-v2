<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - Choose Cluster</title>
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

    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-green-900 mb-3">
            Select your <span class="text-blue-900">Cluster of Electives</span>
        </h1>
        <p class="text-gray-600">Choose the specialization that best fits your goals.</p>
    </div>

    <form action="{{ url('/student/save-cluster') }}" method="POST" class="w-full max-w-5xl">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            
            @php
                $track = session('track', 'academic');
                $clusters = $track === 'academic' 
                    ? [
                        ['id' => 'assh', 'name' => 'Arts, Social Sciences & Humanities (ASSH)'],
                        ['id' => 'be', 'name' => 'Business & Entrepreneurship (BE)'],
                        ['id' => 'stem', 'name' => 'Science, Technology, Engineering & Math (STEM)']
                      ]
                    : [
                        ['id' => 'css', 'name' => 'Computer System Servicing (CSS)'],
                        ['id' => 'vgd', 'name' => 'Visual Graphics & Design'],
                        ['id' => 'eim', 'name' => 'Electrical Installation & Maintenance (EIM)'],
                        ['id' => 'epas', 'name' => 'Electronics Product Assembly & Servicing (EPAS)']
                      ];
            @endphp

            @foreach($clusters as $cluster)
            <label class="cursor-pointer group">
                <input type="radio" name="cluster" value="{{ $cluster['id'] }}" class="peer sr-only" required>
                <div class="h-48 bg-white rounded-3xl shadow-sm border-4 border-transparent peer-checked:border-blue-900 flex items-center justify-center p-6 text-center transition-all transform hover:-translate-y-2">
                    <span class="text-xl font-bold text-gray-800 group-hover:text-blue-900">
                        {{ $cluster['name'] }}
                    </span>
                </div>
            </label>
            @endforeach

        </div>

        <div class="flex justify-center gap-6">
            <a href="{{ url('/student/track-selection') }}" class="bg-white border-2 border-blue-900 text-blue-900 font-bold text-lg px-12 py-3 rounded-full hover:bg-gray-50 transition">BACK</a>
            <button type="submit" class="bg-blue-900 text-white font-bold text-lg px-12 py-3 rounded-full shadow-lg hover:bg-blue-800 transition">NEXT</button>
        </div>
    </form>
</body>
</html>