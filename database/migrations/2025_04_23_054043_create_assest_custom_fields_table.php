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
        Schema::create('asset_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->index()->nullable()->default(null);
            $table->foreign('custom_field_id')
                ->references('id')->on('custom_fields')
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
        Schema::dropIfExists('asset_custom_fields');
    }
};
