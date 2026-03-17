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
        Schema::create('tiles', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->enum("tile_type", ['navigation_tile', 'smartsheet_form'])->default('navigation_tile');
            $table->longText("description")->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('portal_id')->index()->nullable()->default(null);
            $table->foreign('portal_id')
                    ->references('id')->on('portals')
                    ->onDelete('cascade');
            $table->string("link_url")->nullable();
            $table->string("tile_image")->nullable();
            $table->string("grid_size")->nullable();
            $table->string("tile_url")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiles');
    }
};
