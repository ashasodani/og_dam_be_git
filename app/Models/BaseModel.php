<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


abstract class BaseModel extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
   // protected static $logOnlyDirty = true;
    use HasFactory;

    /**
     * purpose to generate uuid when create
     */
    protected static function boot()
    {
      
        parent::boot();
        
    }
    protected $auditEmptyValues = false;

//    public function transformAudit(array $data): array
//     {
//         if (empty($this->getDirty())) {
//             return []; // Returning an empty array will skip audit log
//         }

//         return $data;
//     }
}