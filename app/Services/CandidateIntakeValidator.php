<?php

namespace App\Services;

use App\Enums\CandidateFormQuestionType;
use App\Models\CandidateFormQuestion;
use App\Models\ComplianceDocument;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CandidateIntakeValidator
{
    /**
     * @param  Collection<int, CandidateFormQuestion>  $questions
     * @param  Collection<int, ComplianceDocument>  $documents
     * @param  array<string, mixed>  $input
     * @return array{answers: array<string, mixed>, acknowledged_document_ids: list<int>}
     */
    public function validate(Collection $questions, Collection $documents, array $input): array
    {
        $answers = [];
        $errors = [];

        foreach ($questions as $question) {
            $key = $question->field_key;
            $value = $input['answers'][$key] ?? null;

            try {
                $answers[$key] = $this->normalizeAnswer($question, $value);
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    $errors["answers.{$key}".($field !== 'value' ? ".{$field}" : '')] = $messages;
                }
            }
        }

        $requiredDocumentIds = $documents
            ->where('require_acknowledgment', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $acknowledged = collect($input['acknowledged_documents'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        foreach ($requiredDocumentIds as $documentId) {
            if (! in_array($documentId, $acknowledged, true)) {
                $errors['acknowledged_documents'][] = 'You must acknowledge all required compliance documents.';
                break;
            }
        }

        if (! filter_var($input['authorization_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $errors['authorization_accepted'][] = 'You must authorize this background screening to continue.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'answers' => $answers,
            'acknowledged_document_ids' => $acknowledged,
        ];
    }

    /**
     * @return mixed
     */
    private function normalizeAnswer(CandidateFormQuestion $question, mixed $value)
    {
        return match ($question->field_type) {
            CandidateFormQuestionType::Text,
            CandidateFormQuestionType::Textarea => $this->normalizeText($question, $value),
            CandidateFormQuestionType::Date => $this->normalizeDate($question, $value),
            CandidateFormQuestionType::Select => $this->normalizeSelect($question, $value),
            CandidateFormQuestionType::YesNo => $this->normalizeYesNo($question, $value),
            CandidateFormQuestionType::AddressHistory => $this->normalizeAddressHistory($question, $value),
            CandidateFormQuestionType::WorkHistory => $this->normalizeWorkHistory($question, $value),
        };
    }

    private function normalizeText(CandidateFormQuestion $question, mixed $value): ?string
    {
        $text = is_string($value) ? trim($value) : '';

        if ($text === '') {
            if ($question->is_required) {
                throw ValidationException::withMessages(['value' => ["{$question->label} is required."]]);
            }

            return null;
        }

        return $text;
    }

    private function normalizeDate(CandidateFormQuestion $question, mixed $value): ?string
    {
        $text = is_string($value) ? trim($value) : '';

        if ($text === '') {
            if ($question->is_required) {
                throw ValidationException::withMessages(['value' => ["{$question->label} is required."]]);
            }

            return null;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            throw ValidationException::withMessages(['value' => ["{$question->label} must be a valid date."]]);
        }

        return $text;
    }

    private function normalizeSelect(CandidateFormQuestion $question, mixed $value): ?string
    {
        $text = is_string($value) ? trim($value) : '';
        $options = $question->optionList();

        if ($text === '') {
            if ($question->is_required) {
                throw ValidationException::withMessages(['value' => ["{$question->label} is required."]]);
            }

            return null;
        }

        if ($options !== [] && ! in_array($text, $options, true)) {
            throw ValidationException::withMessages(['value' => ["{$question->label} has an invalid selection."]]);
        }

        return $text;
    }

    private function normalizeYesNo(CandidateFormQuestion $question, mixed $value): ?string
    {
        $text = is_string($value) ? trim($value) : '';

        if ($text === '') {
            if ($question->is_required) {
                throw ValidationException::withMessages(['value' => ["{$question->label} is required."]]);
            }

            return null;
        }

        if (! in_array($text, ['yes', 'no'], true)) {
            throw ValidationException::withMessages(['value' => ["{$question->label} must be Yes or No."]]);
        }

        return $text;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeAddressHistory(CandidateFormQuestion $question, mixed $value): array
    {
        $rows = is_array($value) ? array_values($value) : [];
        $normalized = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $line1 = trim((string) ($row['line1'] ?? ''));
            $line2 = trim((string) ($row['line2'] ?? ''));
            $city = trim((string) ($row['city'] ?? ''));
            $state = trim((string) ($row['state'] ?? ''));
            $postal = trim((string) ($row['postal'] ?? ''));
            $from = trim((string) ($row['from'] ?? ''));
            $to = trim((string) ($row['to'] ?? ''));

            if ($line1 === '' && $city === '' && $state === '' && $postal === '' && $from === '' && $to === '') {
                continue;
            }

            if ($line1 === '' || $city === '' || $state === '' || $postal === '' || $from === '') {
                throw ValidationException::withMessages([
                    "{$index}" => ["Address #".($index + 1).' is incomplete.'],
                ]);
            }

            $normalized[] = [
                'line1' => $line1,
                'line2' => $line2 !== '' ? $line2 : null,
                'city' => $city,
                'state' => $state,
                'postal' => $postal,
                'from' => $from,
                'to' => $to !== '' ? $to : null,
            ];
        }

        if ($question->is_required && $normalized === []) {
            throw ValidationException::withMessages(['value' => ["{$question->label} requires at least one address."]]);
        }

        return $normalized;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeWorkHistory(CandidateFormQuestion $question, mixed $value): array
    {
        $rows = is_array($value) ? array_values($value) : [];
        $normalized = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $employer = trim((string) ($row['employer'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            $city = trim((string) ($row['city'] ?? ''));
            $state = trim((string) ($row['state'] ?? ''));
            $from = trim((string) ($row['from'] ?? ''));
            $to = trim((string) ($row['to'] ?? ''));
            $reason = trim((string) ($row['reason_for_leaving'] ?? ''));

            if ($employer === '' && $title === '' && $city === '' && $state === '' && $from === '' && $to === '') {
                continue;
            }

            if ($employer === '' || $title === '' || $from === '') {
                throw ValidationException::withMessages([
                    "{$index}" => ["Work history #".($index + 1).' is incomplete.'],
                ]);
            }

            $normalized[] = [
                'employer' => $employer,
                'title' => $title,
                'city' => $city !== '' ? $city : null,
                'state' => $state !== '' ? $state : null,
                'from' => $from,
                'to' => $to !== '' ? $to : null,
                'reason_for_leaving' => $reason !== '' ? $reason : null,
            ];
        }

        if ($question->is_required && $normalized === []) {
            throw ValidationException::withMessages(['value' => ["{$question->label} requires at least one employer."]]);
        }

        return $normalized;
    }

    public function activeQuestionsFor(Organization $organization): Collection
    {
        return $organization->candidateFormQuestions()->active()->get();
    }

    public function acknowledgmentDocumentsFor(Organization $organization): Collection
    {
        return $organization->complianceDocuments()->requiringAcknowledgment()->get();
    }
}
