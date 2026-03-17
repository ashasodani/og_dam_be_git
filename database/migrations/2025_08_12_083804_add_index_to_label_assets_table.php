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
        Schema::table('label_assets', function (Blueprint $table) {
            $table->index(['label_id', 'asset_id'], 'asset_label_assets_asset_id_label_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('label_assets', function (Blueprint $table) {
             $table->dropIndex('asset_label_assets_asset_id_label_id_index');
        });
    }
};
