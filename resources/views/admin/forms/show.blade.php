@extends('admin.layout')

@section('page_title', 'Share Form')

@section('content')
<div class="py-8">

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500 text-white rounded-lg shadow-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.forms.index') }}"
           class="text-sm text-[#00923F] font-bold hover:underline">← Back to Forms</a>
        <a href="{{ route('admin.forms.edit', $form) }}"
           class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl transition shadow-sm">
            Edit Form
        </a>
    </div>

    <div class="grid grid-cols-3 gap-6">

        {{-- Left: Form details (2/3 width) --}}
        <div class="col-span-2 space-y-6">

            {{-- Header info --}}
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-8">
                <h2 class="text-2xl font-black text-[#003918] mb-1">{{ $form->title }}</h2>
                @if($form->description)
                    <p class="text-gray-500 text-sm">{{ $form->description }}</p>
                @endif

                <div class="flex items-center gap-3 mt-4">
                    @if($form->firestore_doc_id)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-100">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            Synced to Firestore
                        </span>
                        <span class="text-xs text-gray-400 font-mono">{{ $form->firestore_doc_id }}</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100">
                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                            Not yet synced — edit and save to push to Firestore
                        </span>
                    @endif
                </div>
            </div>

            {{-- Questions preview --}}
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-8">
                <h3 class="text-xs font-black text-[#004225] uppercase tracking-widest mb-5">
                    Questions ({{ count($form->schema) }})
                </h3>
                <div class="space-y-3">
                    @forelse($form->schema as $i => $q)
                        <div class="flex items-center gap-4 bg-gray-50 rounded-xl px-5 py-3.5">
                            <span class="w-7 h-7 rounded-lg bg-[#00923F]/10 text-[#00923F] text-xs flex items-center justify-center font-black flex-shrink-0">
                                {{ $i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-[#003918]">{{ $q['label'] }}</p>
                                <p class="text-xs font-mono text-amber-600">id: {{ $q['id'] }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 rounded font-bold">
                                    {{ $q['type'] }}
                                </span>
                                @if(($q['validation'] ?? 'none') !== 'none')
                                    <span class="text-xs bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded font-bold">
                                        {{ $q['validation'] }}
                                    </span>
                                @endif
                                @if($q['required'] ?? false)
                                    <span class="text-xs bg-red-50 text-red-600 border border-red-100 px-2 py-0.5 rounded font-bold">
                                        required
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm italic">No questions defined yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: QR + Share (1/3 width) --}}
        <div class="space-y-5">

            {{-- QR Code --}}
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 text-center">
                <h3 class="text-xs font-black text-[#004225] uppercase tracking-widest mb-4">QR Code</h3>
                <div class="bg-white rounded-xl border-2 border-gray-100 p-3 inline-block mb-3">
                    <img src="{{ $qrUrl }}" alt="QR Code for {{ $form->title }}" width="180" height="180">
                </div>
                <p class="text-xs text-gray-400">Students scan this to open the enrollment form on their phone.</p>
                <a href="{{ $qrUrl }}" download="qr_{{ $form->share_token }}.png"
                   class="mt-3 inline-block text-xs text-[#00923F] font-bold hover:underline">
                    Download QR Image
                </a>
            </div>

            {{-- Public Link --}}
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                <h3 class="text-xs font-black text-[#004225] uppercase tracking-widest mb-3">Public Link</h3>
                <div class="bg-gray-50 border-2 border-gray-100 rounded-xl px-4 py-3 mb-3 break-all">
                    <a href="{{ $studentViewUrl }}" target="_blank"
                       class="text-xs text-[#00923F] hover:underline font-mono">
                        {{ $studentViewUrl }}
                    </a>
                </div>
                <button
                    onclick="navigator.clipboard.writeText('{{ $studentViewUrl }}').then(() => { this.textContent = '✓ Copied!'; setTimeout(() => this.textContent = 'Copy Full Link', 2000) })"
                    class="w-full bg-[#00923F] hover:bg-[#004225] text-white text-xs font-black uppercase tracking-widest py-2.5 rounded-xl transition shadow-sm"
                >
                    Copy Full Link
                </button>
            </div>

            {{-- Share Token --}}
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-5">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Share Token</h3>
                <p class="font-mono text-xs text-gray-500 break-all">{{ $form->share_token }}</p>
                <p class="text-xs text-gray-400 mt-2">
                    This is the <span class="font-mono">?id=</span> value the Student View reads to fetch the schema from Firestore.
                </p>
            </div>

            {{-- Archive --}}
            <form method="POST" action="{{ route('admin.forms.destroy', $form) }}"
                  onsubmit="return confirm('Archive this form?')">
                @csrf @method('DELETE')
                <button class="w-full bg-gray-100 hover:bg-red-50 text-gray-400 hover:text-red-500 text-xs font-black uppercase tracking-widest py-2.5 rounded-xl transition">
                    Archive This Form
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
