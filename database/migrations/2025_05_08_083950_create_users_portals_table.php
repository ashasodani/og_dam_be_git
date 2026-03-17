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
        Schema::create('users_portals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->nullable()->default(null);
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
                
            $table->foreignId('portal_id')->index()->nullable()->default(null);
            $table->foreign('portal_id')
                    ->references('id')->on('portals')
                    ->onDelete('cascade');
          

            $table->foreignId('role_id')->index()->nullable()->default(null);
            $table->foreign('role_id')
                    ->references('id')->on('roles')
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
        Schema::dropIfExists('users_portals');
    }
};
