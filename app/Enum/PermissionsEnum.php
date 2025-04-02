<?php

namespace App\Enum;

enum PermissionsEnum: string
{


    ////////////////////////////////////////
    case ViewPermissions = 'view permissions';
    case CreatePermissions = 'create permissions';
    case EditPermissions = 'edit permissions';
    case UpdatePermissions = 'update permissions';
    case DestroyPermissions = 'destroy permissions';
    case ViewRoles = 'view roles';
    case ViewFileManager = 'view file mangager';
    case ViewAreas = 'view areas';
    case ViewDashboard = 'view dashboard';
}
