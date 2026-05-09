<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">

    <aside class="w-[300px] h-screen bg-[#F6FFFA] p-8 flex flex-col border-r border-green-100 shadow-sm sticky top-0">
        
        <div class="mb-10 px-2">
            <h1 class="text-[#003918] text-4xl font-[800] tracking-tight leading-tight">Serblsko</h1>
            <p class="text-[#003918] text-sm font-medium opacity-70 mt-1">
                Enrollment Management System
            </p>
        </div>

        <nav class="flex-1 overflow-y-auto">
            <ul class="space-y-1.5 list-none p-0">
                
                @php
                    function isActive($route) {
                        return request()->routeIs($route)
                            ? 'bg-[#00923F]/10 text-[#00923F] border-l-4 border-[#00923F]'
                            : 'text-[#003918] hover:bg-[#00923F]/5';
                    }
                    $userRole = strtolower(session('user_role'));
                @endphp

                <li>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-10 px-4 py-3 text-[#003918] font-semibold rounded-xl hover:bg-[#00923F]/5 transition-colors group {{ isActive('admin.dashboard') }}">
                        <div class="w-6 flex justify-center shrink-0">
                            <svg class="w-5 h-5 fill-current opacity-80 group-hover:opacity-100" viewBox="0 0 16 16">
                                <path d="M6 1H2a1 1 0 0 0-1 1v5.6a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1m7.8 0H9.9a1 1 0 0 0-1 1v2.1a1 1 0 0 0 1 1h3.9a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1m-.1 6.8h-3.9a1 1 0 0 0-1 1v5.6a1 1 0 0 0 1 1h3.9a1 1 0 0 0 1-1V8.2a1 1 0 0 0-1-1m-7.8 2.8H2a1 1 0 0 0-1 1v2.1a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-2.1a1 1 0 0 0-1-1"/>
                            </svg>
                        </div>
                        <span>Dashboard</span>
                    </a>
                </li>

                <ul class="space-y-1.5 list-none p-0">
                    <li class="rounded-xl transition-colors group {{ request()->is('admin/students*') ? 'bg-[#00923F]/10 text-[#00923F] border-l-4 border-[#00923F]' : 'text-[#003918] hover:bg-[#00923F]/5' }}">
                        <a href="{{ route('admin.students') }}" class="flex items-center gap-10 px-4 py-3 font-semibold">
                            <div class="w-6 flex justify-center shrink-0">
                                <svg class="w-5 h-5 fill-current opacity-80 group-hover:opacity-100" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                            </div>
                            <span>Students</span>
                        </a>
                    </li>
                    <li class="rounded-xl transition-colors group {{ request()->is('admin/sections*') ? 'bg-[#00923F]/10 text-[#00923F] border-l-4 border-[#00923F]' : 'text-[#003918] hover:bg-[#00923F]/5' }}">
                        <a href="{{ route('admin.sections.index') }}" class="flex items-center gap-10 px-4 py-3 font-semibold">
                            <div class="w-6 flex justify-center shrink-0">
                                <svg class="w-5 h-5 fill-current opacity-80 group-hover:opacity-100" viewBox="0 0 20 20">
                                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                                </svg>
                            </div>
                            <span>Section Setup</span>
                        </a>
                    </li>
                </ul>

                @if(in_array($userRole, ['super_admin', 'admin']))
                <li>
                    <a href="{{ route('admin.verification') }}" class="flex items-center gap-10 px-4 py-3 text-[#003918] font-semibold rounded-xl hover:bg-[#00923F]/5 transition-colors group {{ isActive('admin.verification') }}">
                        <div class="w-6 flex justify-center shrink-0">
                            <svg class="w-6 h-6 fill-current opacity-80 group-hover:opacity-100" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M14 2.01H6a1.997 1.997 0 0 0-1.99 2l-.01 16a1.997 1.997 0 0 0 1.99 2H18a2.006 2.006 0 0 0 2-2v-12Zm.863 14.958l-.9 1.557a.236.236 0 0 1-.279.099l-1.125-.45a3.3 3.3 0 0 1-.756.44l-.17 1.189a.23.23 0 0 1-.226.189h-1.8a.224.224 0 0 1-.225-.19l-.17-1.187a3 3 0 0 1 1.216.19l.171 1.188a3 3 0 0 1 .765.44l1.116-.45a.23.23 0 0 1 .28.1l.9 1.557a.234.234 0 0 1-.055.288l-.954.747a2.4 2.4 0 0 1 .036.44a4 4 0 0 1-.036.442l.963.747a.234.234 0 0 1 .054.288M13 9.01v-5.5l5.5 5.5Z" />
                            </svg>
                        </div>
                        <span>Verification</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.forms.index') }}"
                    class="flex items-center gap-10 px-4 py-3 text-[#003918] font-semibold rounded-xl hover:bg-[#00923F]/5 transition-colors group
                            {{ request()->is('admin/forms*') ? 'bg-[#00923F]/10 text-[#00923F] border-l-4 border-[#00923F]' : '' }}">
                        <div class="w-6 flex justify-center shrink-0">
                            <svg class="w-5 h-5 fill-current opacity-80 group-hover:opacity-100" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 13h8v1H8v-1zm0 2h8v1H8v-1zm0 2h5v1H8v-1z"/>
                            </svg>
                        </div>
                        <span class="leading-tight">Form Builder</span>
                    </a>
                </li>
                <li> 
                    <a href="{{ route('admin.syncconflict') }}" class="flex items-center gap-10 px-4 py-3 text-[#003918] font-semibold rounded-xl hover:bg-[#00923F]/5 transition-colors group {{ isActive('admin.syncconflict') }}">
                        <div class="w-6 flex justify-center shrink-0">
                            <svg class="w-6 h-6 fill-current opacity-80 group-hover:opacity-100" viewBox="0 0 30 30">
                                <path d="M15.0127 5V1.25L10.0127 6.25L15.0127 11.25V7.5C19.1502 7.5 22.5127 10.8625 22.5127 15C22.5127 16.2625 22.2002 17.4625 21.6377 18.5L23.4627 20.325C24.4755 18.7336 25.0132 16.8863 25.0127 15C25.0127 9.475 20.5377 5 15.0127 5ZM15.0127 22.5C10.8752 22.5 7.5127 19.1375 7.5127 15C7.5127 13.7375 7.8252 12.5375 8.3877 11.5L6.5627 9.675C5.54989 11.2664 5.01217 13.1137 5.0127 15C5.0127 20.525 9.48769 25 15.0127 25V28.75L20.0127 23.75L15.0127 18.75V22.5Z" />
                            </svg>
                        </div>
                        <span class="leading-tight">Sync Conflicts</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.hardware.index') }}" class="flex items-center gap-10 px-4 py-3 text-[#003918] font-semibold rounded-xl hover:bg-[#00923F]/5 transition-colors group {{ isActive('admin.hardware.index') }}">
                        <div class="w-6 flex justify-center shrink-0">
                            <svg class="w-6 h-6 fill-current opacity-80 group-hover:opacity-100" viewBox="0 0 24 24">
                                <path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <span class="leading-tight">Hardware</span>
                    </a>
                </li>
                @endif
            </ul>
        </nav>

        <div class="pt-6 border-t border-green-300">
            @if($userRole === 'super_admin')
            <a href="{{ route('admin.accessmanagement') }}" class="flex items-center gap-10 px-4 py-3 text-[#003918] font-semibold rounded-xl hover:bg-[#00923F]/5 transition-colors group {{ isActive('admin.accessmanagement') }}">
                <div class="w-6 flex justify-center shrink-0">
                    <svg class="w-6 h-6 fill-current opacity-80" viewBox="0 0 24 24">
                        <path d="M19.6 3C18 3 13.1 1 12 1s-6 2-7.6 2c-1.4 0-2.4 1.2-2.4 2.6V12c0 5.6 4.7 9.8 8.6 11.7.9.4 1.9.4 2.8 0 3.9-1.9 8.6-6.1 8.6-11.7V5.6C22 4.2 21 3 19.6 3zm-7.6 16.5c-2.3 0-4.1-1.8-4.1-4.1s1.8-4.1 4.1-4.1s4.1 1.8 4.1 4.1s-1.8 4.1-4.1 4.1zm0-10.2c-1.7 0-3-1.3-3-3s1.3-3 3-3s3 1.3 3 3s-1.3 3-3 3z"/>
                    </svg>
                </div>
                <span>Access Management</span>
            </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="flex w-full items-center gap-10 px-4 py-3 text-[#003918] font-semibold rounded-xl hover:bg-red-50 hover:text-red-700 transition-colors group focus:outline-none">
                    <div class="w-6 flex justify-center shrink-0">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 14 14">
                            <path fill-rule="evenodd" d="M2.23 1.358a.4.4 0 0 1 .27-.109h7c.103 0 .2.04.27.109a.35.35 0 0 1 .105.247v.962a.625.625 0 1 0 1.25 0v-.962c0-.43-.174-.84-.48-1.14A1.64 1.64 0 0 0 9.5 0h-7c-.427 0-.84.167-1.145.466s-.48.71-.48 1.14v10.79c0 .43.174.839.48 1.14c.3.3.72.46 1.14.46h7c.43 0 .84-.16 1.14-.46s.48-.71.48-1.14v-.96a.625.625 0 1 0-1.25 0v.96c0 .09-.04.18-.11.25a.4.4 0 0 1-.27.11h-7a.4.4 0 0 1-.27-.11a.35.35 0 0 1-.1-.25V1.6c0-.09.04-.18.11-.25m8.03 3.06l-.38.58v1.38H5.5a.625.625 0 1 0 0 1.25h4.38V9a.625.625 0 0 0 1.07.44l2-2a.625.625 0 0 0 0-.88l-2-2a.625.625 0 0 0-.68-.13" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

</body>
</html>