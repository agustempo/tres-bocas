<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muelles_comunidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('zona')->nullable();
            $table->string('referencia')->nullable(); // landmark nearby
            $table->unsignedSmallInteger('confirmaciones')->default(0);
            $table->boolean('verificado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muelles_comunidad');
    }
};
