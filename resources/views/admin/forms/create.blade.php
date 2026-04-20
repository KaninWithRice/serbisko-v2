@extends('admin.layout')

@section('page_title', isset($form) ? 'Edit Form' : 'New Form')

@section('content')
<div class="py-8">

    <div class="mb-6">
        <a href="{{ route('admin.forms.index') }}"
           class="text-sm text-[#00923F] font-bold hover:underline">← Back to Forms</a>
    </div>

    <div
        x-data="formBuilder({{ isset($form) ? json_encode($form->schema) : '[]' }})"
        x-cloak
    >
        <form
            method="POST"
            action="{{ isset($form) ? route('admin.forms.update', $form) : route('admin.forms.store') }}"
            @submit.prevent="submitForm($el)"
        >
            @csrf
            @if(isset($form))
                @method('PUT')
            @endif

            {{-- Meta Card --}}
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-8 mb-6">
                <h3 class="text-xs font-black text-[#004225] uppercase tracking-widest mb-5">Form Details</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Form Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text" name="title"
                            value="{{ old('title', $form->title ?? '') }}"
                            required
                            class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-[#003918] placeholder-gray-300 text-sm focus:border-[#00923F] focus:outline-none transition"
                            placeholder="e.g. Pre-Enrollment Form SY 2026–2027"
                        >
                        @error('title')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Description <span class="text-gray-400 normal-case font-normal">(shown to students)</span>
                        </label>
                        <textarea
                            name="description"
                            rows="2"
                            class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-[#003918] placeholder-gray-300 text-sm focus:border-[#00923F] focus:outline-none transition resize-none"
                            placeholder="Optional instructions for students filling out this form"
                        >{{ old('description', $form->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Field ID Cheat Sheet --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
                <p class="text-amber-700 font-black text-xs uppercase tracking-widest mb-3">
                    ⚠️ Field ID Reference — Must match sync.js field names exactly
                </p>
                <p class="text-amber-600 text-xs mb-3">
                    The <span class="font-mono font-bold">Field ID</span> you set for each question becomes the key in the Firestore response document.
                    sync.js reads <span class="font-mono">raw.lrn</span>, <span class="font-mono">raw.first_name</span>, etc. — so the Field ID must match exactly.
                    <strong>Click any name to copy it.</strong>
                </p>
                <div class="grid grid-cols-4 gap-x-6 gap-y-1.5">
                    @foreach ([
                        'lrn', 'first_name', 'last_name', 'middle_name',
                        'extension_name', 'birthday', 'sex', 'age',
                        'place_of_birth', 'mother_tongue', 'school_year',
                        'curr_house_number', 'curr_street', 'curr_barangay',
                        'curr_city', 'curr_province', 'curr_zip_code', 'curr_country',
                        'is_perm_same_as_curr', 'perm_house_number', 'perm_street', 'perm_barangay',
                        'perm_city', 'perm_province', 'perm_zip_code', 'perm_country',
                        'mother_last_name', 'mother_first_name', 'mother_middle_name', 'mother_contact_number',
                        'father_last_name', 'father_first_name', 'father_middle_name', 'father_contact_number',
                        'guardian_last_name', 'guardian_first_name', 'guardian_middle_name', 'guardian_contact_number',
                    ] as $field)
                        <span
                            class="font-mono text-xs text-amber-800 hover:text-[#00923F] hover:font-bold cursor-pointer transition"
                            title="Click to copy: {{ $field }}"
                            onclick="navigator.clipboard.writeText('{{ $field }}').then(() => { this.style.color='#00923F'; setTimeout(() => this.style.color='', 800) })"
                        >{{ $field }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Questions --}}
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-black text-[#004225] uppercase tracking-widest">Questions</h3>
                <button type="button" @click="addQuestion()"
                        class="inline-flex items-center gap-1.5 bg-[#00923F] hover:bg-[#004225] text-white text-xs font-black uppercase tracking-widest px-4 py-2 rounded-lg transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add Question
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(question, index) in questions" :key="question._key">
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">

                        {{-- Row 1: Label + Field ID + Delete --}}
                        <div class="flex items-start gap-4 mb-5">

                            {{-- Label --}}
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Question Label <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    :name="`questions[${index}][label]`"
                                    x-model="question.label"
                                    required
                                    class="w-full border-2 border-gray-100 rounded-xl px-3 py-2.5 text-sm text-[#003918] placeholder-gray-300 focus:border-[#00923F] focus:outline-none transition"
                                    placeholder="e.g. Learner Reference Number"
                                >
                            </div>

                            {{-- Field ID --}}
                            <div class="w-56">
                                <label class="block text-xs font-bold text-amber-600 uppercase tracking-wider mb-1.5">
                                    Field ID <span class="text-red-500">*</span>
                                    <span class="text-gray-400 normal-case font-normal">(sync.js key)</span>
                                </label>
                                <input
                                    type="text"
                                    :name="`questions[${index}][field_id]`"
                                    x-model="question.field_id"
                                    required
                                    pattern="^[a-z_]+$"
                                    title="Only lowercase letters and underscores (e.g. lrn, first_name)"
                                    class="w-full border-2 border-amber-200 rounded-xl px-3 py-2.5 text-sm text-amber-800 placeholder-amber-200 focus:border-amber-400 focus:outline-none transition font-mono bg-amber-50"
                                    placeholder="e.g. lrn"
                                >
                                <p x-show="question.field_id && !/^[a-z_]+$/.test(question.field_id)"
                                   class="text-red-500 text-xs mt-1">
                                    Lowercase letters and underscores only.
                                </p>
                            </div>

                            {{-- Delete --}}
                            <button type="button" @click="removeQuestion(index)"
                                    class="mt-6 p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M18 6L6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Row 2: Type / Validation / Required --}}
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Type</label>
                                <select
                                    :name="`questions[${index}][type]`"
                                    x-model="question.type"
                                    class="w-full border-2 border-gray-100 rounded-xl px-3 py-2.5 text-sm text-[#003918] focus:border-[#00923F] focus:outline-none transition"
                                >
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Validation Rule</label>
                                <select
                                    :name="`questions[${index}][validation]`"
                                    x-model="question.validation"
                                    class="w-full border-2 border-gray-100 rounded-xl px-3 py-2.5 text-sm text-[#003918] focus:border-[#00923F] focus:outline-none transition"
                                >
                                    <option value="none">None</option>
                                    <option value="numeric_only">Numeric Only</option>
                                    <option value="lrn_format">LRN Format (12 digits)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Required?</label>
                                <label class="flex items-center gap-2 cursor-pointer mt-2.5">
                                    <input type="hidden" :name="`questions[${index}][required]`" value="0">
                                    <input
                                        type="checkbox" :name="`questions[${index}][required]`" value="1"
                                        x-model="question.required"
                                        class="w-4 h-4 rounded accent-[#00923F]"
                                    >
                                    <span class="text-sm text-[#003918] font-medium">Yes, required</span>
                                </label>
                            </div>
                        </div>

                        {{-- Row 3: Placeholder --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                Placeholder <span class="text-gray-400 normal-case font-normal">(optional hint text)</span>
                            </label>
                            <input
                                type="text"
                                :name="`questions[${index}][placeholder]`"
                                x-model="question.placeholder"
                                class="w-full border-2 border-gray-100 rounded-xl px-3 py-2.5 text-sm text-[#003918] placeholder-gray-300 focus:border-[#00923F] focus:outline-none transition"
                                placeholder="e.g. Enter your 12-digit LRN"
                            >
                        </div>

                        {{-- Hints --}}
                        <p x-show="question.validation === 'lrn_format'"
                           class="mt-2 text-xs text-amber-600 font-medium">
                            ⚠️ LRN Format enforces exactly 12 numeric digits. sync.js will reject responses with an invalid LRN.
                        </p>
                        <p x-show="question.field_id && question.field_id !== 'lrn' && question.validation === 'lrn_format'"
                           class="mt-1 text-xs text-red-500 font-medium">
                            ⚠️ LRN Format validation should only be used when Field ID is <span class="font-mono">lrn</span>.
                        </p>
                    </div>
                </template>
            </div>

            <div x-show="questions.length === 0"
                 class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-12 text-center text-gray-400 text-sm mb-4">
                No questions yet. Click "Add Question" to get started.
            </div>

            @error('questions')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
            @enderror

            {{-- Submit --}}
            <div class="mt-8 flex items-center gap-4">
                <button
                    type="submit"
                    class="bg-[#00923F] hover:bg-[#004225] text-white font-black uppercase tracking-widest px-8 py-3 rounded-xl transition shadow-lg shadow-green-200 text-xs"
                >
                    {{ isset($form) ? '💾 Save & Re-sync to Firestore' : '🚀 Create & Sync to Firestore' }}
                </button>
                <a href="{{ route('admin.forms.index') }}"
                   class="text-gray-400 hover:text-gray-600 text-sm font-medium transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function formBuilder(initialQuestions) {
    return {
        questions: (initialQuestions || []).map((q, i) => ({
            ...q,
            _key:        q.id || ('new_' + i),
            field_id:    q.field_id || q.id || '',
            required:    Boolean(q.required),
            placeholder: q.placeholder || '',
        })),

        addQuestion() {
            this.questions.push({
                _key:        'new_' + Date.now(),
                id:          '',
                field_id:    '',
                label:       '',
                type:        'text',
                validation:  'none',
                required:    false,
                placeholder: '',
            });
        },

        removeQuestion(index) {
            this.questions.splice(index, 1);
        },

        submitForm(formEl) {
            const invalid = this.questions.some(
                q => !q.field_id || !/^[a-z_]+$/.test(q.field_id)
            );
            if (invalid) {
                alert('All questions must have a valid Field ID (lowercase letters and underscores only, e.g. lrn, first_name).');
                return;
            }
            formEl.submit();
        },
    };
}
</script>
@endsection
