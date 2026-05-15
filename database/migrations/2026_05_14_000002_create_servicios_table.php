<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('operador')->nullable();
            $table->string('tipo'); // lancha_colectiva | remise_fluvial | carga | especial
            $table->text('descripcion')->nullable();
            $table->string('contacto')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('verificado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
