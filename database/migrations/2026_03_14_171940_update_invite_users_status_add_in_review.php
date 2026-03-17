<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE invite_users DROP CONSTRAINT IF EXISTS invite_users_status_check");
        DB::statement("ALTER TABLE invite_users ADD CONSTRAINT invite_users_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'accepted'::character varying, 'declined'::character varying, 'in-review'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE invite_users DROP CONSTRAINT IF EXISTS invite_users_status_check");
        DB::statement("ALTER TABLE invite_users ADD CONSTRAINT invite_users_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'accepted'::character varying, 'declined'::character varying]::text[]))");
    }
};
