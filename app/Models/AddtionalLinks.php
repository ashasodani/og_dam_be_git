<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class AddtionalLinks extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'link_url',
        'link_icon',
        'portal_id',
        'position'
    ];

      /**
     * The attributes that define table
     *
     * 
     */
    protected $table = 'additional_links';

  
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
