<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sections_subfolder', function (Blueprint $table) {
              $table->index(['section_id', 'sub_folder_id'], 'sections_subfolder_section_id_subfolder_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections_subfolder', function (Blueprint $table) {
            $table->dropIndex('sections_subfolder_section_id_subfolder_id_index');
        });
    }
};
