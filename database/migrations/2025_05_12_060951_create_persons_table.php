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
        Schema::create('persons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('contact_person_name',500)->nullable();
            $table->string('designation',500)->nullable();
            $table->string('contact_person_email_id',500)->nullable();
            $table->string('contact_person_no',500)->nullable();
            $table->enum('is_email',[0,1])->default(1)->comment('0-Pending 1-Done');
            $table->enum('status',[0,1])->default(1)->comment('0-Inactive 1-Active');
            $table->foreignId('company_id')->index()->nullable()->default(null);
              
            $table->foreign('company_id')
                ->references('id')->on('companies')
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
        Schema::dropIfExists('persons');
    }
};
