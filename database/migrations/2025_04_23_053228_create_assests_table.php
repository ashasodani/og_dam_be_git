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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->enum("type", ['genericfile', 'person', 'press', 'text', 'font', 'color', 'externalmedium'])
            ->default('genericfile');
            $table->longText("description")->nullable();
            $table->string("asset_key")->unique();
            $table->string("asset_url")->nullable();
            $table->string("url")->nullable();
            $table->string("filename")->nullable();
            $table->string("thumbnail_image")->nullable();
            $table->string("extension")->nullable();
            $table->boolean("recent_upload")->default(false);
            $table->foreignId('created_by')->index()->nullable()->default(null);
            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('cascade');
            $table->string("links")->nullable();
            $table->date("publish_date")->nullable();
            $table->string("hex")->nullable();
            $table->string("rgb")->nullable();
            $table->string("cmyk")->nullable();
            $table->string("pantagone_coated")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
