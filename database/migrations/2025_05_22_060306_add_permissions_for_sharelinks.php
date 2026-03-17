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
                "module_name"     => "Share Links",
                "sub_module_name" => "Create",
                "name"            => $this->permissionSlugs["share_links"]["create"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Share Links",
                "sub_module_name" => "List",
                "name"            => $this->permissionSlugs["share_links"]["list"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Share Links",
                "sub_module_name" => "Delete",
                "name"            => $this->permissionSlugs["share_links"]["delete"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Share Links",
                "sub_module_name" => "Update",
                "name"            => $this->permissionSlugs["share_links"]["update"],
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
            $this->permissionSlugs["share_links"]["list"],
            $this->permissionSlugs["share_links"]["update"],
            $this->permissionSlugs["share_links"]["delete"],
            $this->permissionSlugs["share_links"]["create"],
        ])->forceDelete();
    }
};
