<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class InviteUsers extends BaseModel
{
    use SoftDeletes,HasRoles;
    
    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> The mass assignable attributes.
     */
    protected $fillable = [
       'email',
       'status',
       'invite_link',
    ];

     /**
     * The attributes that define table
     *
     * 
     */
    protected $table = 'invite_users';

     /**
     * Define a many-to-many relationship with the workspaces model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function invite_user_workspaces()
    {
      //  dd("jisd");
        return $this->belongsToMany(Workspaces::class,'invite_users_workspace', 'user_id')
                ->withPivot('role_id')
                ->withTimestamps();
    }

     /**
     * Define a many-to-many relationship with the portal model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany The sections relationship.
     */
    public function invite_user_portals()
    {
        return $this->belongsToMany(Portals::class,'invite_users_portals', 'user_id', 'portal_id')
                ->withPivot('role_id')
                ->withTimestamps();
    }
}
