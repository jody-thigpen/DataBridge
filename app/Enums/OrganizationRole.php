<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case ClientAdmin = 'client_admin';
    case HrManager = 'hr_manager';
    case Recruiter = 'recruiter';
    case Viewer = 'viewer';
}
