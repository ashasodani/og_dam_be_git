<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Countries extends BaseModel
{
    use SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'country_name',
        'sheet_name'
    ];

    /**
     * Define a has-many relationship with the companies model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The companies relationship.
     */
    public function companies()
    {
        return $this->hasMany(Companies::class, 'country_id');
    }

    // /**
    //  * Define a many-to-many relationship with the workspace model.
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The WorkSpace relationship.
    //  */
    // public function workspaces()
    // {
    //     return $this->belongsToMany(Workspaces::class, 'workspace_tags', 'tag_id', 'workspace_id');
    // }
}
