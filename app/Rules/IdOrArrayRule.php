<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class IdOrArrayRule implements ValidationRule
{
    protected string $table;
    protected string $column;

    public function __construct(string $table, string $column = 'id')
    {
        $this->table  = $table;
        $this->column = $column;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $values = is_array($value) ? $value : [$value]; 

        foreach ($values as $val) {
            if (! is_numeric($val)) {
                $fail("The {$attribute} field must be an integer or array of integers.");
                return;
            }

            $exists = DB::table($this->table)
                ->where($this->column, $val)
                ->whereNull('deleted_at')
                ->exists();

            if (! $exists) {
                $fail("The selected {$attribute} is invalid or deleted.");
                return;
            }
        }
    }
}
