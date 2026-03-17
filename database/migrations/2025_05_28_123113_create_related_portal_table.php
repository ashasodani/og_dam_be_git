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
        Schema::create('related_portal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_id')->index()->nullable()->default(null);
            $table->foreign('portal_id')
                ->references('id')->on('portals')
                ->onDelete('cascade');
                
            $table->foreignId('related_portal_id')->index()->nullable()->default(null);
            $table->foreign('related_portal_id')
                    ->references('id')->on('portals')
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
        Schema::dropIfExists('related_portal');
    }
};
