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
        // Permission::insert([
        //     [
        //         "uuid" => Str::uuid(),
        //         "module_name" => "DataImport",
        //         "sub_module_name" => "List",
        //         "slug" => $this->permissionSlugs["dataimport"]["list"],
        //     ],
        //     [
        //         "uuid" => Str::uuid(),
        //         "module_name" => "DataImport",
        //         "sub_module_name" => "Change Status",
        //         "slug" => $this->permissionSlugs["dataimport"]["change-status"],
        //     ],
        //     [
        //         "uuid" => Str::uuid(),
        //         "module_name" => "DataImport",
        //         "sub_module_name" => "Create",
        //         "slug" => $this->permissionSlugs["dataimport"]["create"],
        //     ],
        //     [
        //         "uuid" => Str::uuid(),
        //         "module_name" => "DataImport",
        //         "sub_module_name" => "Delete",
        //         "slug" => $this->permissionSlugs["dataimport"]["delete"],
        //     ],
        //     [
        //         "uuid" => Str::uuid(),
        //         "module_name" => "DataImport",
        //         "sub_module_name" => "View",
        //         "slug" => $this->permissionSlugs["dataimport"]["view"],
        //     ],
        // ]);

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
            $this->permissionSlugs["dataimport"]["create"],
            $this->permissionSlugs["dataimport"]["list"],
            $this->permissionSlugs["dataimport"]["view"],
            $this->permissionSlugs["dataimport"]["delete"],
            $this->permissionSlugs["dataimport"]["change-status"],
        ])->forceDelete();
    }
};
