<?php

use Illuminate\Database\Migrations\Migration;
use App\Enums\PermissionEnum;
use App\Models\Permission;

return new class extends Migration
{
    protected $permissionSlugs;

    public function __construct() {
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
    }
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::insert([
            [
                "module_name" => "Workspaces",
                "sub_module_name" => "Create",
                "name" => $this->permissionSlugs["workspaces"]["create"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Workspaces",
                "sub_module_name" => "List",
                "name" => $this->permissionSlugs["workspaces"]["list"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Workspaces",
                "sub_module_name" => "Delete",
                "name" => $this->permissionSlugs["workspaces"]["delete"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Workspaces",
                "sub_module_name" => "Update",
                "name" => $this->permissionSlugs["workspaces"]["update"],
                'guard_name' => 'api'
            ]
        ]);

        // $permissions = Permission::pluck('id');
        // $role = Role::create(["id" => RoleEnum::AdminRoleId->value, "name" => "Admin"]);

        // if ($role) {
        //     $role->permissions()->sync($permissions);
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn("name", [
            $this->permissionSlugs["workspaces"]["list"],
            $this->permissionSlugs["workspaces"]["update"],
            $this->permissionSlugs["workspaces"]["delete"],
            $this->permissionSlugs["workspaces"]["create"],
        ])->forceDelete();
    }
};
