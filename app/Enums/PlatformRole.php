<?php

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Operations = 'operations';
    case ClientManager = 'client_manager';
    case Support = 'support';
}
