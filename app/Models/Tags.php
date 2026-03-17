<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Tags extends BaseModel
{
    use SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Define a many-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function assets()
    {
        return $this->belongsToMany(Assets::class, 'asset_tags', 'tag_id', 'asset_id');
    }

    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The WorkSpace relationship.
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspaces::class, 'workspace_tags', 'tag_id', 'workspace_id');
    }
}
