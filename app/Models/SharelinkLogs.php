<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class SharelinkLogs extends BaseModel
{
    use SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
        'share_link_id',
        'email',
        'ip_address',
        'is_login_user',
        'user_agent',
    ];

    protected $table = 'sharelink_logs';
}
