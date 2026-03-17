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
        Schema::create('sharelink_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_link_id')->index()->nullable()->default(null);
            $table->foreign('share_link_id')
                ->references('id')->on('share_links')
                ->onDelete('cascade');
            $table->string('email')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->boolean('is_login_user')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sharelink_logs');
    }
};
