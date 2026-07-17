<?php

namespace App\Enums;

enum CandidateFormQuestionType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Date = 'date';
    case Select = 'select';
    case YesNo = 'yes_no';
    case AddressHistory = 'address_history';
    case WorkHistory = 'work_history';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Short text',
            self::Textarea => 'Long text',
            self::Date => 'Date',
            self::Select => 'Dropdown',
            self::YesNo => 'Yes / No',
            self::AddressHistory => 'Address history',
            self::WorkHistory => 'Work history',
        };
    }

    public function isStructured(): bool
    {
        return match ($this) {
            self::AddressHistory, self::WorkHistory => true,
            default => false,
        };
    }
}
