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
        Schema::create('portals', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("slug")->index();
            $table->enum("privacy", ['public', 'private', 'stealth'])->default('public');
            $table->longText("description")->nullable();
            $table->string("link")->nullable();
            $table->string("thumbnail_image")->nullable();
            $table->string("header_image")->nullable();
            $table->string("header_url")->nullable();
            $table->string("url")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portals');
    }
};
