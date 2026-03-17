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
        Schema::create('workspace_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->index()->nullable()->default(null);
            $table->foreign('tag_id')
                ->references('id')->on('tags')
                ->onDelete('cascade');
            $table->foreignId('workspace_id')->index()->nullable()->default(null);
            $table->foreign('workspace_id')
                ->references('id')->on('workspaces')
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
        Schema::dropIfExists('workspace_tags');
    }
};
