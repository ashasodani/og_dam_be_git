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
        Schema::table('subfolder_assets', function (Blueprint $table) {
               $table->index(['asset_id', 'sub_folder_id'], 'subfolder_assets_asset_id_sub_folder_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subfolder_assets', function (Blueprint $table) {
              $table->dropIndex('subfolder_assets_asset_id_sub_folder_id_index');
        });
    }
};
