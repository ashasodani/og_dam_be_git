<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Collections extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'slug',
        'privacy',
        'description',
        'created_by',
    ];

    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The worksapce relationship.
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspaces::class, 'workspace_collections', 'collection_id', 'workspace_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Sections::class, 'collection_sections', 'collection_id', 'section_id');
    }

    public function assets()
    {
        return $this->belongsToMany(Assets::class, 'collection_assets', 'collection_id', 'asset_id');
    }
}
