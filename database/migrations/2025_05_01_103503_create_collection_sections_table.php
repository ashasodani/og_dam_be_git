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
        Schema::create('collection_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->index()->nullable()->default(null);
            $table->foreign('collection_id')
                ->references('id')->on('collections')
                ->onDelete('cascade');

            $table->foreignId('section_id')->index()->nullable()->default(null);
            $table->foreign('section_id')
                ->references('id')->on('sections')
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
        Schema::dropIfExists('collection_sections');
    }
};
