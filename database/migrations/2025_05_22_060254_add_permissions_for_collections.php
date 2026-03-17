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
                "module_name"     => "Collections",
                "sub_module_name" => "Create",
                "name"            => $this->permissionSlugs["collections"]["create"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Collections",
                "sub_module_name" => "List",
                "name"            => $this->permissionSlugs["collections"]["list"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Collections",
                "sub_module_name" => "Delete",
                "name"            => $this->permissionSlugs["collections"]["delete"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Collections",
                "sub_module_name" => "Update",
                "name"            => $this->permissionSlugs["collections"]["update"],
                'guard_name'      => 'api',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn("name", [
            $this->permissionSlugs["collections"]["list"],
            $this->permissionSlugs["collections"]["update"],
            $this->permissionSlugs["collections"]["delete"],
            $this->permissionSlugs["collections"]["create"],
        ])->forceDelete();
    }
};
