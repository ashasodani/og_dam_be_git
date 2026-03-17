<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Workspaces extends BaseModel
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
        'privacy',
        'description',
        'thumbnail_image',
        'url',
    ];

    /**
     * Define a many-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function sections()
    {
        return $this->belongsToMany(Sections::class, 'workspace_sections', 'workspace_id', 'section_id');
    }

    /**
     * Define a many-to-many relationship with the collections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function collections()
    {
        return $this->belongsToMany(Collections::class, 'workspace_collections', 'workspace_id', 'collection_id');
    }

    /**
     * Define a many-to-many relationship with the labels model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function labels()
    {
        return $this->belongsToMany(Labels::class, 'workspace_labels', 'workspace_id', 'label_id');
    }

    /**
     * Define a many-to-many relationship with the user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function users()
    {
        return $this->belongsToMany(InviteUsers::class, 'invite_users_workspace', 'user_id')
            ->withPivot('role_id')
            ->withTimestamps();
        //  return $this->belongsToMany(User::class, 'users_workspaces', 'workspace_id', 'user_id');
    }

    /**
     * Define a many-to-many relationship with the user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function workspaceuser()
    {
        return $this->belongsToMany(InviteUsers::class, 'users_workspace', 'workspace_id', 'user_id')
            ->withPivot('role_id')
            ->withTimestamps();
        //  return $this->belongsToMany(User::class, 'users_workspaces', 'workspace_id', 'user_id');
    }

    public function assets()
    {
        return $this->belongsToMany(Assets::class, 'workspace_assets', 'workspace_id', 'asset_id')
            ->withTimestamps()
            ->withoutGlobalScopes()
            ->whereNull('workspace_assets.deleted_at')
            ->whereNull('assets.deleted_at');
    }

    public function assetss()
    {
        return $this->hasManyThrough(Assets::class, Sections::class);
    }

    /**
     * Define a many-to-many relationship with the subfolder model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The tags relationship.
     */
    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'workspace_tags', 'workspace_id', 'tag_id');
    }

    public function users_workspaces()
    {
        return $this->belongsToMany(User::class, 'users_workspaces', 'workspace_id', 'user_id');
    }

    public function subfolders()
    {
        return $this->belongsToMany(SubFolders::class, 'workspace_sub_folder', 'workspace_id', 'subfolder_id');
    }

    public function sharelinks()
    {
        return $this->belongsToMany(ShareLinks::class, 'share_links_workspace', 'workspace_id', 'share_link_id');
    }
}
