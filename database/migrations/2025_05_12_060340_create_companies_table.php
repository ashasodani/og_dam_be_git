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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->index();
            $table->string('company_website')->nullable()->default(null);
            $table->string('port_name');
            $table->foreignId('port_id')->index()->nullable()->default(null);
            $table->foreign('port_id')
                ->references('id')->on('ports')
                ->onDelete('cascade');
            $table->foreignId('country_id')->index()->nullable()->default(null);
                $table->foreign('country_id')
                    ->references('id')->on('countries')
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
        Schema::dropIfExists('companies');
    }
};
