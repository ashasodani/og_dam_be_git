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
        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string('url')->nullable();
            $table->boolean('is_private')->default(false);
            $table->boolean('is_email_address')->default(false);
            $table->boolean('is_password')->default(false);
            $table->string('s_password')->nullable();
            $table->boolean('is_expired')->default(false);
            $table->dateTime('expiry_date')->nullable();
            $table->string('timezone')->nullable();
            $table->unsignedBigInteger('create_by')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->enum('status', [0, 1])->default(1)->comment('1 - Active, 0 - Expired');
            $table->enum('context_type', ['collection', 'organization', 'brand-folder'])->default('brand-folder');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_links');
    }
};
