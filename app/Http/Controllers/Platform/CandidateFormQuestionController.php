<?php

namespace App\Http\Controllers\Platform;

use App\Enums\CandidateFormQuestionType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\CandidateFormQuestion;
use App\Models\Organization;
use App\Models\User;
use App\Services\CandidateFormQuestionDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CandidateFormQuestionController extends Controller
{
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'field_key' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('candidate_form_questions', 'field_key')->where('organization_id', $organization->id),
            ],
            'field_type' => ['required', Rule::enum(CandidateFormQuestionType::class)],
            'options_text' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $fieldType = CandidateFormQuestionType::from($validated['field_type']);
        $options = $this->parseOptions($validated['options_text'] ?? null, $fieldType);

        CandidateFormQuestion::query()->create([
            'organization_id' => $organization->id,
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'field_key' => $validated['field_key'] ?? Str::slug($validated['label'], '_'),
            'field_type' => $fieldType->value,
            'options' => $options,
            'is_required' => $request->boolean('is_required', true),
            'is_active' => true,
            'sort_order' => $validated['sort_order'] ?? (($organization->candidateFormQuestions()->max('sort_order') ?? 0) + 10),
        ]);

        return back()->with('status', 'Candidate form question added.');
    }

    public function update(Request $request, Organization $organization, CandidateFormQuestion $candidateFormQuestion): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);
        abort_unless($candidateFormQuestion->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'field_type' => ['required', Rule::enum(CandidateFormQuestionType::class)],
            'options_text' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $fieldType = CandidateFormQuestionType::from($validated['field_type']);

        $candidateFormQuestion->update([
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'field_type' => $fieldType->value,
            'options' => $this->parseOptions($validated['options_text'] ?? null, $fieldType),
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? $candidateFormQuestion->sort_order,
        ]);

        return back()->with('status', 'Candidate form question updated.');
    }

    public function destroy(Request $request, Organization $organization, CandidateFormQuestion $candidateFormQuestion): RedirectResponse
    {
        abort_unless($this->canManageCatalog($request->user()), 403);
        abort_unless($candidateFormQuestion->organization_id === $organization->id, 404);

        $candidateFormQuestion->delete();

        return back()->with('status', 'Candidate form question removed.');
    }

    public function seedDefaults(
        Request $request,
        Organization $organization,
        CandidateFormQuestionDefaults $defaults,
    ): RedirectResponse {
        abort_unless($this->canManageCatalog($request->user()), 403);

        $created = $defaults->seedForOrganization($organization);

        $message = $created > 0
            ? "Added {$created} default candidate form question(s)."
            : 'Default candidate form questions are already present.';

        return back()->with('status', $message);
    }

    private function canManageCatalog(?User $user): bool
    {
        return $user !== null && $user->hasPermission(Permission::PlatformCatalogManage);
    }

    /**
     * @return list<string>|null
     */
    private function parseOptions(?string $optionsText, CandidateFormQuestionType $fieldType): ?array
    {
        if ($fieldType !== CandidateFormQuestionType::Select) {
            return null;
        }

        $options = collect(preg_split('/\r\n|\r|\n/', (string) $optionsText) ?: [])
            ->map(fn (string $option) => trim($option))
            ->filter()
            ->values()
            ->all();

        return $options === [] ? null : $options;
    }
}
