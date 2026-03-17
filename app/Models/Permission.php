<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as SpatiePermission;

use OwenIt\Auditing\Contracts\Auditable;

class Permission extends SpatiePermission
{
    use HasFactory;
    protected $guarded = [''];
    protected $fillable = [
        'name',
        'guard_name',
        'permission_key',
    ];
}
