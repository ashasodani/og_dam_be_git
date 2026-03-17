<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceNotification extends Model
{
    use SoftDeletes;

    /**
     * The attributes that define table
     *
     *
     */
    protected $table = 'workspace_notification';

    protected $fillable = [
        'workspace_id',
        'collection_id',
        'is_mail',
        'in_app',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
