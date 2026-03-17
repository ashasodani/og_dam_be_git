<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;

class WorkspaceSections extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'section_id',
        'workspace_id',
    ];

     /**
     * The attributes that define table
     *
     * 
     */
    protected $table = 'workspace_sections';
}
