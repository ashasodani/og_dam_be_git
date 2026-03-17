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
                "module_name"     => "Asset",
                "sub_module_name" => "Create",
                "name"            => $this->permissionSlugs["asset"]["create"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Asset",
                "sub_module_name" => "List",
                "name"            => $this->permissionSlugs["asset"]["list"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Asset",
                "sub_module_name" => "Update",
                "name"            => $this->permissionSlugs["asset"]["update"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Asset",
                "sub_module_name" => "Delete",
                "name"            => $this->permissionSlugs["asset"]["delete"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Asset",
                "sub_module_name" => "Share",
                "name"            => $this->permissionSlugs["asset"]["share"],
                'guard_name'      => 'api',
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn("name", [
            $this->permissionSlugs["asset"]["list"],
            $this->permissionSlugs["asset"]["share"],
            $this->permissionSlugs["asset"]["delete"],
            $this->permissionSlugs["asset"]["create"],
            $this->permissionSlugs["asset"]["update"]
        ])->forceDelete();
    }
};
