<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - First Login</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .custom-gradient { background: linear-gradient(90deg, #1b5e20 0%, #2e7d32 40%, #f3f4f6 100%); }
    </style>
</head>
<body class="custom-gradient min-h-screen flex items-center justify-center p-8">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-blue-900 px-8 py-6 text-white text-center">
            <h1 class="text-2xl font-bold">Welcome, Ka-Compre!</h1>
            <p class="text-sm opacity-80 mt-1">Please set your new account password to continue.</p>
        </div>

        <div class="p-8">
            <form action="{{ url('/first-login/update') }}" method="POST" class="space-y-6" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">New Password</label>
                    <input type="password" name="new_password" required
                        class="w-full px-5 py-3 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-blue-900 focus:bg-white outline-none transition-all"
                        placeholder="••••••••">
                    @error('new_password')
                        <p class="text-red-600 text-[10px] mt-1 italic ml-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Confirm Password</label>
                    <input type="password" name="new_password_confirmation" required
                        class="w-full px-5 py-3 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-blue-900 focus:bg-white outline-none transition-all"
                        placeholder="••••••••">
                </div>

                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <h3 class="text-blue-900 text-[10px] font-black uppercase tracking-widest mb-2 flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        Security Requirement
                    </h3>
                    <ul class="text-[10px] text-blue-800 space-y-1">
                        <li class="flex items-center"><svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg> Minimum 8 characters</li>
                        <li class="flex items-center"><svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg> Must include uppercase & lowercase</li>
                        <li class="flex items-center"><svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg> Must include numbers & symbols</li>
                    </ul>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading"
                        class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center space-x-2 disabled:opacity-70">
                        <svg x-show="loading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Updating Password...' : 'Save & Logout'"></span>
                    </button>
                    <p class="text-center text-[10px] text-gray-400 mt-4 italic">
                        After updating, you will be required to log in again with your new password.
                    </p>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
