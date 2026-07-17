<?php

namespace App\Services;

use App\Enums\CandidateFormQuestionType;
use App\Models\CandidateFormQuestion;
use App\Models\Organization;

class CandidateFormQuestionDefaults
{
    /**
     * @return list<array{
     *     label: string,
     *     help_text: ?string,
     *     field_key: string,
     *     field_type: CandidateFormQuestionType,
     *     options: ?list<string>,
     *     is_required: bool,
     *     sort_order: int
     * }>
     */
    public function definitions(): array
    {
        return [
            [
                'label' => 'Confirm legal name',
                'help_text' => 'Enter your full legal name as it appears on government-issued ID.',
                'field_key' => 'legal_name',
                'field_type' => CandidateFormQuestionType::Text,
                'options' => null,
                'is_required' => true,
                'sort_order' => 10,
            ],
            [
                'label' => 'Date of birth',
                'help_text' => null,
                'field_key' => 'date_of_birth',
                'field_type' => CandidateFormQuestionType::Date,
                'options' => null,
                'is_required' => true,
                'sort_order' => 20,
            ],
            [
                'label' => 'Mobile phone',
                'help_text' => null,
                'field_key' => 'mobile_phone',
                'field_type' => CandidateFormQuestionType::Text,
                'options' => null,
                'is_required' => true,
                'sort_order' => 30,
            ],
            [
                'label' => 'Other names used',
                'help_text' => 'Include maiden names, aliases, or previous legal names.',
                'field_key' => 'other_names',
                'field_type' => CandidateFormQuestionType::Textarea,
                'options' => null,
                'is_required' => false,
                'sort_order' => 40,
            ],
            [
                'label' => 'Address history',
                'help_text' => 'Provide residential addresses for the last 7 years.',
                'field_key' => 'address_history',
                'field_type' => CandidateFormQuestionType::AddressHistory,
                'options' => null,
                'is_required' => true,
                'sort_order' => 50,
            ],
            [
                'label' => 'Work history',
                'help_text' => 'Provide employment history for the last 7 years.',
                'field_key' => 'work_history',
                'field_type' => CandidateFormQuestionType::WorkHistory,
                'options' => null,
                'is_required' => true,
                'sort_order' => 60,
            ],
        ];
    }

    public function seedForOrganization(Organization $organization): int
    {
        $created = 0;

        foreach ($this->definitions() as $definition) {
            $question = CandidateFormQuestion::query()->firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'field_key' => $definition['field_key'],
                ],
                [
                    'tenant_id' => $organization->tenant_id,
                    'label' => $definition['label'],
                    'help_text' => $definition['help_text'],
                    'field_type' => $definition['field_type']->value,
                    'options' => $definition['options'],
                    'is_required' => $definition['is_required'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ],
            );

            if ($question->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
