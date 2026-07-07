<?php

namespace App\Enums;

enum SearchTypeCode: string
{
    case CountyCriminal = 'county_criminal';
    case NationalCriminal = 'national_criminal';
    case SocialSecurityTrace = 'social_security_trace';

    public function label(): string
    {
        return match ($this) {
            self::CountyCriminal => 'County criminal',
            self::NationalCriminal => 'National criminal',
            self::SocialSecurityTrace => 'Social security trace',
        };
    }

    public function defaultDescription(): string
    {
        return match ($this) {
            self::CountyCriminal => 'County-level criminal record search.',
            self::NationalCriminal => 'National criminal database search.',
            self::SocialSecurityTrace => 'Social Security number validation and address history trace.',
        };
    }
}
