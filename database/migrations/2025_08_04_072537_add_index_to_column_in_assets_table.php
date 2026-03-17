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
        Schema::table('assets', function (Blueprint $table) {
            $table->index('asset_key');
            $table->index('filename');
            $table->index('name');
            $table->index('extension');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['asset_key']);
            $table->dropIndex(['filename']);
            $table->dropIndex(['name']);
            $table->dropIndex(['extension']);
        });
    }
};
