<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Sections extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'default_asset_type',
        'position',
        'asset_type',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($section) {
            // Delete all subfolders
            foreach ($section->subfolders as $subfolder) {
                $subfolder->delete(); // this should trigger its own cascade if needed
                $subfolder->assets()->delete();
                $subfolder->assets()->detach();
            }

            // Delete all assets
            foreach ($section->assets as $asset) {
                $asset->delete();
            }

            // Detach from workspaces (pivot table)
            $section->workspaces()->detach();
            $section->subfolders()->detach();
            $section->assets()->detach();
        });
    }


    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The worksapce relationship.
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspaces::class, 'workspace_sections', 'section_id', 'workspace_id');
    }

    public function collections()
    {
        return $this->belongsToMany(Collections::class, 'collection_sections', 'section_id', 'collection_id');
    }
    /**
     * Define a many-to-many relationship with the subfolders model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The subfolders relationship.
     */

    public function subfolders()
    {
        return $this->belongsToMany(SubFolders::class, 'sections_subfolder', 'section_id', 'sub_folder_id');
    }

    /**
     * Define a many-to-many relationship with the assets model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The assets relationship.
     */

    public function assets()
    {
        return $this->belongsToMany(Assets::class, 'section_assets', 'section_id', 'asset_id');
    }

    /**
     * Define a many-to-many relationship with the share links model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The share links relationship.
     */
    public function sharelinks()
    {
        return $this->belongsToMany(ShareLinks::class, 'share_links_sections', 'section_id', 'share_link_id');
    }
}
