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
        Schema::table('section_assets', function (Blueprint $table) {
            $table->index(['section_id', 'asset_id'], 'section_asset_section_id_asset_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('section_assets', function (Blueprint $table) {
              $table->dropIndex('section_asset_section_id_asset_id_index');
        });
    }
};
