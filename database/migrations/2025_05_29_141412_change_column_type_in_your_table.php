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
            $table->string('hex')->change()->nullable();
            $table->string('rgb')->change()->nullable();
            $table->string('cmyk')->change()->nullable();
            $table->string('pantagone_coated')->change()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('your', function (Blueprint $table) {
            $table->date('hex')->change()->nullable();
            $table->date('rgb')->change()->nullable();
            $table->date('cmyk')->change()->nullable();
            $table->date('pantagone_coated')->change()->nullable();
        });
    }
};
