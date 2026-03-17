<?php
namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionGroups = PermissionEnum::Slugs->getAll();

        // 1. Insert all permissions (if not exist)
        foreach ($permissionGroups as $module => $permissions) {
            foreach ($permissions as $action => $slug) {
                Permission::firstOrCreate(['name' => $slug], [
                    'guard_name'      => 'api',
                    'module_name'     => $this->formatModuleName($module),
                    'sub_module_name' => ucfirst($action), // e.g., Create, List, Update
                ]);
            }
        }

        // Get all slugs
        $allSlugs = Permission::pluck('name')->toArray();

        // 2. Assign All to Super Admin & Admin
        $this->assignPermissionsToRole('Super Admin', $allSlugs);
        $this->assignPermissionsToRole('Admin', $allSlugs);

        // Collaborator gets all permissions for selected modules
        $collaboratorModules = [
            'workspaces', 'sections', 'portals', 'collections', 'labels', 'tags', 'share_links', 'asset',
        ];
        $collaboratorPermissions = [];
        foreach ($collaboratorModules as $module) {
            if (isset($permissionGroups[$module])) {
                $collaboratorPermissions = array_merge($collaboratorPermissions, array_values($permissionGroups[$module]));
            }
        }
        $this->assignPermissionsToRole('Collaborator', $collaboratorPermissions);

        // Guest gets only list/share permissions
        $guestAccess = [
            'workspaces'  => ['list'],
            'sections'    => ['list'],
            'portals'     => ['list'],
            'collections' => ['list'],
            'labels'      => ['list'],
            'tags'        => ['list'],
            'share_links' => ['list'],
            'asset'       => ['list', 'share'],
        ];

        $guestPermissions = [];
        foreach ($guestAccess as $module => $actions) {
            foreach ($actions as $action) {
                if (isset($permissionGroups[$module][$action])) {
                    $guestPermissions[] = $permissionGroups[$module][$action];
                }
            }
        }
        $this->assignPermissionsToRole('Guest', $guestPermissions);

    }

    private function assignPermissionsToRole(string $roleName, array $permissionSlugs): void
    {
        $role          = Role::firstOrCreate(['name' => $roleName]);
        $permissionIds = Permission::whereIn('name', $permissionSlugs)->pluck('id')->toArray();
        $role->permissions()->sync($permissionIds);
    }

    private function formatModuleName(string $key): string
    {
        return Str::of($key)->replace('_', ' ')->title(); // e.g., 'share_links' => 'Share Links'
    }
}
