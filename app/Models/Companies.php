<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Companies extends BaseModel
{
    use SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
       'company_name',
        'company_website',
        'port_name',
        'port_id',
        'country_id'
    ];

    public function persons()
    {
        return $this->hasMany(Persons::class, 'company_id');
    }

    /**
     * Define a belongs-to relationship with the countries model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The country relationship.
     */
    public function country()
    {
        return $this->belongsTo(Countries::class, 'country_id');
    }

    /**
     * Define a belongs-to relationship with the ports model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The port relationship.
     */
    public function port()
    {
        return $this->belongsTo(Ports::class, 'port_id');
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
