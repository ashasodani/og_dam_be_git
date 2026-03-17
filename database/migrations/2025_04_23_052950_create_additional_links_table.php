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
        Schema::create('additional_links', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("link_url")->nullable();
            $table->string("link_icon")->nullable();
            $table->foreignId('portal_id')->index()->nullable()->default(null);
            $table->foreign('portal_id')
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
        Schema::dropIfExists('additional_links');
    }
};
