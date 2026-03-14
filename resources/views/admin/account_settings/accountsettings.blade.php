@extends('admin.layout')

@section('page_title', 'Security Settings')

@section('content')

<div class="max-w-full mx-auto p-4 space-y-6 font-sans">

    <div class="bg-[#F7FBF9]/40 rounded-2xl shadow-lg border-l-8 border-[#1a8a44] overflow-hidden relative">
        <div class="p-8 pl-12 flex justify-between items-start">
            <div>
                <p class="text-[10px] font-bold text-[#a0aec0] uppercase tracking-widest mb-1">Registered Name</p>
                <h2 class="text-3xl font-black text-[#003918] uppercase tracking-tight leading-none">
                    {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                </h2>
                <p class="text-[10px] font-bold text-[#718096] uppercase italic mt-2">
                    Born on <span class="text-[#4a5568]">{{ \Carbon\Carbon::parse(auth()->user()->birthday)->format('F d, Y') }}</span>
                </p>
            </div>

            <div class="bg-[#F7FBF9]/40 text-[#2f855a] text-[10px] font-extrabold px-4 py-2 rounded-full uppercase tracking-normal">
                {{ str_replace('_', ' ', auth()->user()->role) }}
            </div>
        </div>
    </div>

    <div class="bg-[#F7FBF9]/40 rounded-2xl shadow-lg border-l-8 border-[#1a8a44] overflow-hidden relative">
        @if(session('success'))
            <div x-data="{ show: true }" 
                x-init="setTimeout(() => show = false, 5000)" 
                x-show="show" 
                x-transition.duration.500ms
                class="mb-6 p-4 bg-[#F7FBF9]/40 border-l-4 border-[#1a8a44] text-[#003918] rounded-r-lg shadow-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-[#1a8a44]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-xs font-bold uppercase tracking-widest italic">
                    {{ session('success') }}
                </span>
            </div>
        @endif
        <form action="{{ route('admin.account.update-password') }}" method="POST" 
            x-data="{ 
                errors: {},
                current_password: '',
                new_password: '',
                new_password_confirmation: '',
                showCurrent: false,
                showNew: false,
                showConfirm: false,
                validate() {
                    this.errors = {};
                    if (!this.current_password) this.errors.current_password = 'Current password is required.';
                    
                    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_=+\[\]{}\\/|;:'-])[A-Za-z\d!@#$%^&*()_=+\[\]{}\\/|;:'-]{8,}$/;
                    if (!this.new_password) {
                        this.errors.new_password = 'New password is required.';
                    } else if (!regex.test(this.new_password)) {
                        this.errors.new_password = 'Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.';
                    }
                    
                    if (!this.new_password_confirmation) {
                        this.errors.new_password_confirmation = 'Please confirm your new password.';
                    } else if (this.new_password !== this.new_password_confirmation) {
                        this.errors.new_password_confirmation = 'Passwords do not match.';
                    }

                    return Object.keys(this.errors).length === 0;
                }
            }" 
            @submit.prevent="if(validate()) $el.submit()"
            class="p-10 pl-14">
            @csrf
            @method('PUT')

            <h3 class="text-2xl font-black text-[#003918] uppercase tracking-tight">Update Password</h3>
            <p class="text-xs text-gray-400 italic mb-6 border-b border-gray-100 pb-4">
                Ensure your account is using a long, random password to stay secure.
            </p>

            <div class="space-y-6 max-w-xl">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <label class="text-[10px] font-bold text-[#003918] uppercase tracking-widest">Current Password</label>
                        
                        <span x-show="errors.current_password" x-text="errors.current_password" class="text-[10px] text-red-600 italic font-medium"></span>
                        
                        @error('current_password')
                            <span class="text-[10px] text-red-600 italic font-medium">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="relative">
                        <input :type="showCurrent ? 'text' : 'password'" 
                            name="current_password" 
                            x-model="current_password" 
                            @input="delete errors.current_password"
                            class="w-full bg-[#f1f3f5] border-none rounded-lg px-4 py-3 text-gray-700 focus:ring-2 focus:ring-[#1a8a44] transition-all pr-12">
                        
                        <button type="button" @click="showCurrent = !showCurrent" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[#1a8a44]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!showCurrent">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="showCurrent" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.403m5.417-1.071A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.403m-5.417 1.071L17.25 17.25M3.75 3.75l16.5 16.5"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <label class="text-[10px] font-bold text-[#003918] uppercase tracking-widest">New Password</label>
                        <span x-show="errors.new_password" x-text="errors.new_password" class="text-[10px] text-red-600 italic font-medium"></span>
                    </div>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'" name="new_password" x-model="new_password" @input="delete errors.new_password"
                            class="w-full bg-[#f1f3f5] border-none rounded-lg px-4 py-3 text-gray-700 focus:ring-2 focus:ring-[#1a8a44] transition-all pr-12">
                        <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[#1a8a44]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!showNew"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="showNew" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.403m5.417-1.071A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.403m-5.417 1.071L17.25 17.25M3.75 3.75l16.5 16.5"/></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <label class="text-[10px] font-bold text-[#003918] uppercase tracking-widest">Confirm New Password</label>
                        <span x-show="errors.new_password_confirmation" x-text="errors.new_password_confirmation" class="text-[10px] text-red-600 italic font-medium"></span>
                    </div>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="new_password_confirmation" x-model="new_password_confirmation" @input="delete errors.new_password_confirmation"
                            class="w-full bg-[#f1f3f5] border-none rounded-lg px-4 py-3 text-gray-700 focus:ring-2 focus:ring-[#1a8a44] transition-all pr-12">
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-[#1a8a44]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!showConfirm"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="showConfirm" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.403m5.417-1.071A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.403m-5.417 1.071L17.25 17.25M3.75 3.75l16.5 16.5"/></svg>
                        </button>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="bg-[#00923F] hover:bg-[#003918] text-white font-bold px-12 py-3 rounded-lg shadow-md transition-all active:scale-95 uppercase text-sm tracking-widest">
                        Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection