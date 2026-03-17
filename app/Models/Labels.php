<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use PhpParser\Node\Stmt\Label;

class Labels extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */

    protected $fillable = ['name', 'parent_key'];
    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The worksapce relationship.
     */

    public function workspaces()
    {
        return $this->belongsToMany(Workspaces::class, 'workspace_labels', 'label_id', 'workspace_id');
    }

    public function parent()
    {
        return $this->belongsTo(Label::class, 'parent_key');
    }

    public function children()
    {
        return $this->hasMany(Label::class, 'parent_key');
    }

    /**
     * Define a many-to-many relationship with the assets model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The assets relationship.
     */
    public function assets()
    {
        return $this->belongsToMany(Assets::class, 'label_assets', 'label_id', 'asset_id');
    }
}
