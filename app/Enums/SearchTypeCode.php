<?php

namespace App\Enums;

enum SearchTypeCode: string
{
    case NationalCriminal = 'national_criminal';
    case CountyCriminal = 'county_criminal';
    case CivilRecords = 'civil_records';
    case MotorVehicleRecords = 'motor_vehicle_records';
    case MedicalCompliance = 'medical_compliance';
    case Verifications = 'verifications';
    case International = 'international';
    case SexOffender = 'sex_offender';
    case SocialSecurityTrace = 'social_security_trace';

    public function label(): string
    {
        return match ($this) {
            self::NationalCriminal => 'National criminal data',
            self::CountyCriminal => 'Criminal records',
            self::CivilRecords => 'Civil records',
            self::MotorVehicleRecords => 'Motor vehicle records',
            self::MedicalCompliance => 'Medical compliance',
            self::Verifications => 'Verifications',
            self::International => 'International',
            self::SexOffender => 'Sex offender',
            self::SocialSecurityTrace => 'Social security trace',
        };
    }

    public function defaultDescription(): string
    {
        return match ($this) {
            self::NationalCriminal => 'Search a wide network of databases for possible criminal history. Ideal for early screening or high-volume checks.',
            self::CountyCriminal => 'Verified court data sourced directly from the county. Confirms convictions, charges, and case details.',
            self::CivilRecords => 'Civil litigation and lawsuit history. Useful for roles involving money, trust, or safety.',
            self::MotorVehicleRecords => 'Driving record including DUIs, license status, and violations. Important for driving and transportation roles.',
            self::MedicalCompliance => 'Healthcare workforce compliance checks including sanctions, exclusions, credentials, and abuse registries.',
            self::Verifications => 'Employment, education, and credential verification domestically and abroad.',
            self::International => 'Global criminal, civil, and verification screening for cross-border and remote hiring.',
            self::SexOffender => 'Sex offender registry search across state and national registries.',
            self::SocialSecurityTrace => 'Social Security number validation and address history trace.',
        };
    }
}
