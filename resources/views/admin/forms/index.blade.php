@extends('admin.layout')

@section('page_title', 'Form Builder')

@section('content')
<div x-data="{}" class="py-8">

    {{-- Notifications --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500 text-white rounded-lg shadow-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-600 text-white rounded-lg shadow-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header Card --}}
    <div class="w-full h-[100px] bg-[#F7FBF9]/50 rounded-[10px] shadow-[0_3px_3px_0_rgba(0,0,0,0.25)] flex items-center px-12 justify-between mb-10">
        <div class="flex flex-col justify-center">
            <div class="flex items-center gap-4">
                <div class="w-4 h-4 bg-[#00923F] rounded-full shrink-0"></div>
                <h2 class="text-[#333333] text-3xl font-extrabold tracking-normal uppercase leading-none">
                    Enrollment Forms
                </h2>
            </div>
            <div class="ml-8 mt-1">
                <p class="text-[#5F748D] text-sm font-medium leading-tight">
                    Build forms for students · Sync schemas to Firestore · Generate QR codes
                </p>
            </div>
        </div>
        <a href="{{ route('admin.forms.create') }}"
           class="inline-flex items-center gap-2 bg-[#00923F] hover:bg-[#004225] text-white text-sm font-black uppercase tracking-widest px-6 py-3 rounded-xl transition shadow-lg shadow-green-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            New Form
        </a>
    </div>

    {{-- Forms Table --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">Form Title</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">Questions</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">Firestore Sync</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">Last Updated</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-[#004225] uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($forms as $form)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-bold text-[#003918] text-sm">{{ $form->title }}</p>
                        @if($form->description)
                            <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $form->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                            {{ count($form->schema) }} question(s)
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($form->firestore_doc_id)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-100">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Synced
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> Not synced
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-400 font-medium">
                        {{ $form->updated_at->format('M d, Y') }}<br>
                        {{ $form->updated_at->diffForHumans() }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.forms.show', $form) }}"
                               class="bg-[#00923F] hover:bg-[#004225] text-white px-4 py-1.5 rounded-lg text-xs font-black uppercase tracking-widest transition shadow-sm">
                                Share / QR
                            </a>
                            <a href="{{ route('admin.forms.edit', $form) }}"
                               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs font-black uppercase tracking-widest transition shadow-sm">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.forms.destroy', $form) }}"
                                  onsubmit="return confirm('Archive this form? It will be soft-deleted.')">
                                @csrf @method('DELETE')
                                <button class="bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-600 px-4 py-1.5 rounded-lg text-xs font-black uppercase tracking-widest transition">
                                    Archive
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-12 h-12 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6M9 16h6M9 8h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="italic text-lg font-medium">No forms yet.</p>
                            <a href="{{ route('admin.forms.create') }}"
                               class="mt-3 text-[#00923F] font-bold text-sm hover:underline">
                                Create your first enrollment form →
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($forms->hasPages())
        <div class="mt-6">{{ $forms->links() }}</div>
    @endif

</div>
@endsection
