<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

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
                "module_name" => "Country",
                "sub_module_name" => "Create",
                "name" => $this->permissionSlugs["countries"]["create"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Country",
                "sub_module_name" => "List",
                "name" => $this->permissionSlugs["countries"]["list"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Country",
                "sub_module_name" => "Delete",
                "name" => $this->permissionSlugs["countries"]["delete"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Country",
                "sub_module_name" => "Update",
                "name" => $this->permissionSlugs["countries"]["update"],
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
        Permission::whereIn("slug", [
            $this->permissionSlugs["countries"]["create"],
            $this->permissionSlugs["countries"]["list"],
           
            $this->permissionSlugs["countries"]["delete"],
        ])->forceDelete();
    }
};
