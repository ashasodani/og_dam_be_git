<?php

namespace Database\Seeders;

use App\Enums\RemovePermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RemoveRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionGroups = RemovePermissionEnum::Slugs->getAll();

        // 1. Insert all permissions (if not exist)
        foreach ($permissionGroups as $module => $permissions) {
            foreach ($permissions as $action => $slug) {
                Permission::where('name', $slug)->delete();
            }
        }
    }
}
