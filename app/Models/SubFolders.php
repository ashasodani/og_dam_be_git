<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class SubFolders extends BaseModel
{
    use SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'slug',
        'section_id',
    ];

     protected static function boot()
    {
        parent::boot();

        static::deleting(function ($subfolder) {
            // Delete all subfolders
            $subfolder->assets()->delete();
            $subfolder->assets()->detach();
            
           // $subfolder->delete();
            // Detach from workspaces (pivot table)
            $subfolder->workspaces()->detach();
            $subfolder->sections()->detach();
           
        });
    }

    /**
     * Define a many-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function sections()
    {
        return $this->belongsToMany(Sections::class, 'sections_subfolder', 'sub_folder_id', 'section_id');
    }

/**
 * Define a many-to-many relationship with the Assest model.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
 */

    public function assets()
    {
        return $this->belongsToMany(Assets::class, 'subfolder_assets', 'sub_folder_id', 'asset_id');
    }

     /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspaces::class, 'workspace_sub_folder', 'subfolder_id', 'workspace_id');
    }
}
