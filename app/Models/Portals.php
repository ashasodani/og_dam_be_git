<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Portals extends BaseModel
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
        'link',
        'description',
        'thumbnail_image',
        'header_image',
        'header_url',
        'url'
    ];

     /**
     * Define a one-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function tiles()
    {
        return $this->hasMany(Portals::class);
    }

     /**
     * Define a one-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function additional_link()
    {
        return $this->hasMany(Portals::class);
    }

    /**
     * Define a one-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function relatedPortal()
    {
        return $this->belongsToMany(portals::class, 'related_portal', 'portal_id', 'related_portal_id');
    }
     /**
     * Define a one-to-many relationship with the sections model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function getrelatedPortal()
    {
        return $this->belongsToMany(portals::class, 'related_portal', 'related_portal_id', 'portal_id');
    }
}
