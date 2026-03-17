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
        Schema::table('asset_tags', function (Blueprint $table) {
            $table->index(['tag_id', 'asset_id'], 'asset_tags_asset_id_tag_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_tags', function (Blueprint $table) {
            $table->dropIndex('asset_tags_asset_id_tag_id_index');
        });
    }
};
