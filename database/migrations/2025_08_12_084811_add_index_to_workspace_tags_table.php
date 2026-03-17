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
        Schema::table('workspace_tags', function (Blueprint $table) {
             $table->index(['tag_id', 'workspace_id'], 'workspace_tags_tag_id_workspace_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_tags', function (Blueprint $table) {
             $table->dropIndex('workspace_tags_tag_id_workspace_id_index');
        });
    }
};
