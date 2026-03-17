<?php
namespace App\Models;

use App\Models\Assets;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class ShareLinks extends BaseModel
{
    use SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'url',
        'is_private',
        'is_email_address',
        'email',
        'is_password',
        's_password',
        'is_expired',
        'expiry_date',
        'timezone',
        'create_by',
        'view_count',
        'status',
        'context_type',
        'is_notify',
        'days',
    ];

    /**
     * Define a many-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function assets()
    {
        return $this->belongsToMany(Assets::class, 'share_links_assets', 'share_link_id', 'asset_id');
    }

    /**
     * Define a many-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function sections()
    {
        return $this->belongsToMany(Sections::class, 'share_links_sections', 'share_link_id', 'section_id');
    }

    /**
     * The user who created this share link.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'create_by');
    }

    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The worksapce relationship.
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspaces::class, 'share_links_workspace', 'share_link_id', 'workspace_id');
    }

    /**
     * Get all logs for the share link.
     */
    public function shareLinkLogs()
    {
        return $this->hasMany(SharelinkLogs::class, 'share_link_id');
    }
}
