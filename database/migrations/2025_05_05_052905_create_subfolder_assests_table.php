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
        Schema::create('subfolder_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->index()->nullable()->default(null);
            $table->foreign('asset_id')
                ->references('id')->on('assets')
                ->onDelete('cascade');

            $table->foreignId('sub_folder_id')->index()->nullable()->default(null);
            $table->foreign('sub_folder_id')
                ->references('id')->on('sub_folders')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subfolder_assets');
    }
};
