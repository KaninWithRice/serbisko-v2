@if(!session()->has('user_id'))
    <script>window.location.href = '/';</script>
@endif
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - SerbIsko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .bg-custom-gradient {
            background: radial-gradient(circle at center, #FFFFFF 10%, #E8F5E9 50%, #2e7d32 100%);
        }
    </style>
</head>
<body class="bg-custom-gradient min-h-screen flex flex-col items-center justify-center p-4 text-center">

    <div class="mb-6 transform transition-all duration-700 scale-100 animate-bounce">
        <div class="h-28 w-28 border-[8px] border-[#0d47a1] rounded-full flex items-center justify-center mx-auto bg-transparent">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[#0d47a1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
    </div>

    <h1 class="text-5xl md:text-6xl font-extrabold text-[#1b5e20] mb-6 leading-tight tracking-tight">
        Thank you<br>for using SerbIsko!
    </h1>
    
    <p class="text-gray-900 text-lg md:text-xl font-medium max-w-lg mx-auto">
        Your effort in completing your enrollment requirements is appreciated. Welcome to TNCHS-SHS, Ka-Compre!
    </p>

    <script>
        setTimeout(() => {
            window.location.href = '/logout'; 
        }, 6000);
    </script>
</body>
</html>