<?php

namespace App\Enums;

enum ComplianceDocumentType: string
{
    case Authorization = 'authorization';
    case Disclosure = 'disclosure';
    case Fcra = 'fcra';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Authorization => 'Authorization form',
            self::Disclosure => 'Disclosure',
            self::Fcra => 'FCRA notice',
            self::Other => 'Other compliance document',
        };
    }
}
