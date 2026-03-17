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
        Schema::create('workspace_sub_folder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subfolder_id')->index()->nullable()->default(null);
            $table->foreign('subfolder_id')
                ->references('id')->on('sub_folders')
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
        Schema::dropIfExists('workspace_subfolders');
    }
};
