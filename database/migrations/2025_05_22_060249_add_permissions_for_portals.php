<?php

use App\Enums\PermissionEnum;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $permissionSlugs;

    public function __construct()
    {
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
    }
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::insert([
            [
                "module_name" => "Portals",
                "sub_module_name" => "Create",
                "name" => $this->permissionSlugs["portals"]["create"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Portals",
                "sub_module_name" => "List",
                "name" => $this->permissionSlugs["portals"]["list"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Portals",
                "sub_module_name" => "Delete",
                "name" => $this->permissionSlugs["portals"]["delete"],
                'guard_name' => 'api'
            ],
            [
                "module_name" => "Portals",
                "sub_module_name" => "Update",
                "name" => $this->permissionSlugs["portals"]["update"],
                'guard_name' => 'api'
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn("name", [
            $this->permissionSlugs["portals"]["list"],
            $this->permissionSlugs["portals"]["update"],
            $this->permissionSlugs["portals"]["delete"],
            $this->permissionSlugs["portals"]["create"],
        ])->forceDelete();
    }
};
