<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomForm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FormBuilderController extends Controller
{
    // ── Firestore helpers ───────────────────────────────────────────────────

    private function firestoreToken(): string
    {
        return cache()->remember('firestore_token', now()->addMinutes(55), function () {
            $keyFile = json_decode(
                file_get_contents(storage_path('app/serviceAccountKey.json')),
                true
            );

            $now     = time();
            $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = base64_encode(json_encode([
                'iss'   => $keyFile['client_email'],
                'scope' => 'https://www.googleapis.com/auth/datastore',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $sigInput = "$header.$payload";
            openssl_sign($sigInput, $sig, $keyFile['private_key'], 'SHA256');
            $jwt = "$sigInput." . base64_encode($sig);

            $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $resp->json('access_token');
        });
    }

    private function toFirestoreDoc(CustomForm $form): array
    {
        return [
            'fields' => [
                'form_id'     => ['integerValue' => (string) $form->id],
                'title'       => ['stringValue'  => $form->title],
                'description' => ['stringValue'  => $form->description ?? ''],
                'schema'      => ['stringValue'  => json_encode($form->schema)],
                'share_token' => ['stringValue'  => $form->share_token],
                'updated_at'  => ['stringValue'  => now()->toIso8601String()],
            ],
        ];
    }

    private function pushToFirestore(CustomForm $form): ?string
    {
        $project = config('services.firebase.project_id');
        $base    = "https://firestore.googleapis.com/v1/projects/{$project}/databases/(default)/documents";
        $token   = $this->firestoreToken();
        $body    = $this->toFirestoreDoc($form);

        try {
            if ($form->firestore_doc_id) {
                $url      = "{$base}/form_schemas/{$form->firestore_doc_id}";
                $response = Http::withToken($token)
                    ->patch($url . '?currentDocument.exists=true', $body);
            } else {
                $response = Http::withToken($token)
                    ->post("{$base}/form_schemas", $body);
            }

            if ($response->failed()) {
                Log::error('Firestore push failed', ['body' => $response->body()]);
                return null;
            }

            $name = $response->json('name');
            return last(explode('/', $name));

        } catch (\Throwable $e) {
            Log::error('Firestore push exception: ' . $e->getMessage());
            return null;
        }
    }

    // ── Shared validation rules ─────────────────────────────────────────────

    private function questionRules(): array
    {
        return [
            'questions'                => 'required|array|min:1',
            'questions.*.label'        => 'required|string|max:255',
            'questions.*.field_id'     => ['required', 'string', 'max:60', 'regex:/^[a-z_]+$/'],
            'questions.*.type'         => 'required|in:text,number,date',
            'questions.*.required'     => 'boolean',
            'questions.*.validation'   => 'required|in:none,numeric_only,lrn_format',
            'questions.*.placeholder'  => 'nullable|string|max:255',
        ];
    }

    /**
     * CRITICAL: question `id` is set to `field_id` (e.g. "lrn", "first_name").
     * The Student View uses question.id as the Firestore document key, so
     * sync.js will find raw.lrn, raw.first_name at the top level — exactly
     * where processDocument() reads them.
     */
    private function buildSchema(array $questions): array
    {
        return collect($questions)->map(function ($q) {
            return [
                'id'          => $q['field_id'],
                'field_id'    => $q['field_id'],
                'label'       => $q['label'],
                'type'        => $q['type'],
                'required'    => (bool) ($q['required'] ?? false),
                'validation'  => $q['validation'],
                'placeholder' => $q['placeholder'] ?? null,
            ];
        })->values()->all();
    }

    // ── CRUD ────────────────────────────────────────────────────────────────

    public function index()
    {
        $forms = CustomForm::where('created_by', auth()->id())
            ->latest()
            ->paginate(20);

        return view('admin.forms.index', compact('forms'));
    }

    public function create()
    {
        return view('admin.forms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge(
            [
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ],
            $this->questionRules()
        ));

        $form = CustomForm::create([
            'created_by'  => auth()->id(),
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'schema'      => $this->buildSchema($data['questions']),
            'share_token' => Str::random(32),
        ]);

        $fsDocId = $this->pushToFirestore($form);
        if ($fsDocId) {
            $form->update(['firestore_doc_id' => $fsDocId]);
        }

        return redirect()
            ->route('admin.forms.show', $form)
            ->with('success', 'Form created and synced to Firestore!');
    }

    public function show(CustomForm $form)
    {
        $studentViewUrl = env('STUDENT_VIEW_BASE_URL') . '?id=' . $form->share_token;

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
            . urlencode($studentViewUrl);

        return view('admin.forms.show', compact('form', 'studentViewUrl', 'qrUrl'));
    }

    public function edit(CustomForm $form)
    {
        return view('admin.forms.edit', compact('form'));
    }

    public function update(Request $request, CustomForm $form)
    {
        $data = $request->validate(array_merge(
            [
                'title'       => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ],
            $this->questionRules()
        ));

        $form->update([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'schema'      => $this->buildSchema($data['questions']),
        ]);

        $fsDocId = $this->pushToFirestore($form->fresh());
        if ($fsDocId && ! $form->firestore_doc_id) {
            $form->update(['firestore_doc_id' => $fsDocId]);
        }

        return redirect()
            ->route('admin.forms.show', $form)
            ->with('success', 'Form updated and re-synced to Firestore!');
    }

    public function destroy(CustomForm $form)
    {
        $form->delete();
        return redirect()
            ->route('admin.forms.index')
            ->with('success', 'Form archived.');
    }
}
