<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Tiles extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'tile_type',
        'position',
        'portal_id',
        'link_url',
        'tile_image',
        'description',
        'grid_size',
        'tile_url'
    ];

     /**
     * Define a many-to-many relationship with the portals model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function portals()
    {
        return $this->belongsTo(Portals::class,'portal_id');
    }

}
