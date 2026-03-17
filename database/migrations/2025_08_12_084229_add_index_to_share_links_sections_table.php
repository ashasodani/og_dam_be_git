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
        Schema::table('share_links_sections', function (Blueprint $table) {
             $table->index(['share_link_id', 'section_id'], 'share_links_sections_share_link_id_section_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('share_links_sections', function (Blueprint $table) {
             $table->dropIndex('share_links_sections_share_link_id_section_id_index');
        });
    }
};
