<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

use OwenIt\Auditing\Contracts\Auditable;

class Role extends SpatieRole
{
    use HasFactory;

    protected $guarded = [''];

      /**
     * Define a many-to-many relationship with the Permission model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The permissions relationship.
     */
    // public function permissions()
    // {
    //     return $this->belongsToMany(Permission::class);
    // }

}
