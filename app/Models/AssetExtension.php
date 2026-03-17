<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetExtension extends Model
{
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'extension',
    ];

     /**
     * The attributes that define table
     *
     * 
     */
    protected $table = 'asset_extension';
}
