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
                "module_name"     => "Label",
                "sub_module_name" => "Create",
                "name"            => $this->permissionSlugs["labels"]["create"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Label",
                "sub_module_name" => "List",
                "name"            => $this->permissionSlugs["labels"]["list"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Label",
                "sub_module_name" => "Update",
                "name"            => $this->permissionSlugs["labels"]["update"],
                'guard_name'      => 'api',
            ],
            [
                "module_name"     => "Label",
                "sub_module_name" => "Delete",
                "name"            => $this->permissionSlugs["labels"]["delete"],
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
            $this->permissionSlugs["labels"]["list"],
            $this->permissionSlugs["labels"]["delete"],
            $this->permissionSlugs["labels"]["create"],
            $this->permissionSlugs["labels"]["update"],
        ])->forceDelete();
    }
};
