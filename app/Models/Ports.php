<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Member
 *
 * Model representing an port.
 *
 * @package App\Models
 */
class Ports extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        "port_name",
        "country_id"
    ];
     /**
     * Defined Table
     *
     * 
     */
   // protected $table = 'port';
}
