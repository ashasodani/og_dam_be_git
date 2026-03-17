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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string("object_key")->nullable();
            $table->enum("object_type", ['asset', 'brandfolder', 'collection'])
            ->default('asset');
            $table->enum("type", ['viewed', 'downloaded', 'searched', 'shared', 'add_attachments'])
            ->default('viewed');
            $table->enum("prepositional_object_type", ['brandfolder', 'collection', 'organization', 'sharelink', 'add_attachments'])
            ->default('sharelink');
            $table->string("prepositional_object_key")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
