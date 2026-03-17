<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class AssetMeta extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'value',
        'asset_id'
    ];

     /**
     * The attributes that define table
     *
     * 
     */
    protected $table = 'asset_meta_data';

    /**
     * The asset that owns the AssetMeta
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assets()
    {
        return $this->belongsTo(Assets::class,"asset_id","id");
    }
}
