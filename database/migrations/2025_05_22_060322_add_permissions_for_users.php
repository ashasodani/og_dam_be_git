<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PermissionEnum;
use App\Models\Permission;

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
                "module_name"     => "User",
                "sub_module_name" => "Create",
                "name"            => $this->permissionSlugs["user"]["create"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "User",
                "sub_module_name" => "List",
                "name"            => $this->permissionSlugs["user"]["list"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "User",
                "sub_module_name" => "Delete",
                "name"            => $this->permissionSlugs["user"]["delete"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "User",
                "sub_module_name" => "Update",
                "name"            => $this->permissionSlugs["user"]["update"],
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
            $this->permissionSlugs["user"]["delete"],
            $this->permissionSlugs["user"]["update"],
            $this->permissionSlugs["user"]["create"],
            $this->permissionSlugs["user"]["list"],
        ])->forceDelete();
    }
};
