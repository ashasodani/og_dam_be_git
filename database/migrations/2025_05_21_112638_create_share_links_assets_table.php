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
        Schema::create('share_links_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_link_id')->index()->nullable()->default(null);
            $table->foreign('share_link_id')
                ->references('id')->on('share_links')
                ->onDelete('cascade');
                
            $table->foreignId('asset_id')->index()->nullable()->default(null);
            $table->foreign('asset_id')
                    ->references('id')->on('assets')
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
        Schema::dropIfExists('share_links_assets');
    }
};
