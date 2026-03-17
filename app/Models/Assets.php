<?php
namespace App\Models;

use App\Models\Tags;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assets extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'asset_key',
        'asset_url',
        'url',
        'filename',
        'extension',
        'recent_upload',
        'thumbnail_image',
        'url',
        'links',
        'publish_date',
        'hex',
        'rgb',
        'cmyk',
        'pantagone_coated',
        'pantagone_uncoated',
        'created_by',
        'is_completed'
    ];

    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The worksapce relationship.
     */
    public function sections()
    {
        return $this->belongsToMany(Sections::class, 'section_assets', 'asset_id', 'section_id');
    }

    /**
     * Define a many-to-many relationship with the subfolder model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The subfolders relationship.
     */
    public function subfolders()
    {
        return $this->belongsToMany(SubFolders::class, 'subfolder_assets', 'asset_id', 'sub_folder_id');
    }

    /**
     * Define a many-to-many relationship with the subfolder model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The subfolders relationship.
     */
    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'asset_tags', 'asset_id', 'tag_id');
    }

    /**
     * Get the meta data associated with the Asset
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metas()
    {
        return $this->hasMany(AssetMeta::class, "asset_id", "id");
    }

    /**
     * Get the labels associated with the Asset
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function labels()
    {
        return $this->belongsToMany(Labels::class, 'label_assets', 'asset_id', 'label_id');
    }

    /**
     * Get the share links associated with the Asset
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function sharelinks()
    {
        return $this->belongsToMany(ShareLinks::class, 'share_links_assets', 'asset_id', 'share_link_id');
    }

    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The worksapce relationship.
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspaces::class, 'workspace_assets', 'asset_id', 'workspace_id');
    }
    /**
     * Get the collections associated with the Asset
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function collections()
    {
        return $this->belongsToMany(Collections::class, 'collection_assets', 'asset_id', 'collection_id');
    }
}
