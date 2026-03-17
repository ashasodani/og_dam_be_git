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
                "module_name"     => "Tags",
                "sub_module_name" => "Create",
                "name"            => $this->permissionSlugs["tags"]["create"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Tags",
                "sub_module_name" => "List",
                "name"            => $this->permissionSlugs["tags"]["list"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Tags",
                "sub_module_name" => "Delete",
                "name"            => $this->permissionSlugs["tags"]["delete"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Tags",
                "sub_module_name" => "Update",
                "name"            => $this->permissionSlugs["tags"]["update"],
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
            $this->permissionSlugs["tags"]["list"],
            $this->permissionSlugs["tags"]["update"],
            $this->permissionSlugs["tags"]["delete"],
            $this->permissionSlugs["tags"]["create"],
        ])->forceDelete();

    }
};
