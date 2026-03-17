<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Attachments extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'url',
        'filename',
    ];

    /**
     * Define a many-to-many relationship with the workspace model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The worksapce relationship.
     */
    // public function assests()
    // {
    //     return $this->belongsToMany(Sections::class, 'section_assests', 'attachment_id', 'section_id');
    // }

    // public function subfolders()
    // {
    //     return $this->belongsToMany(Collections::class, 'collection_sections', 'asset_id', 'subfolder_id');
    // }
}
