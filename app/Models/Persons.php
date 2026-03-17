<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Person
 *
 * Model representing an person.
 *
 * @package App\Models
 */
class Persons extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'contact_person_name',
        'designation',
        'contact_person_email_id',
        'contact_person_no',
        'company_id'
    ];
    protected $table = 'persons';

    public function company()
    {
        return $this->belongsTo(Companies::class);
    }
    
}
