<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->string('tipo'); // suspension | demora_general | ruta_alternativa
            $table->text('descripcion');
            $table->timestamp('valida_desde');
            $table->timestamp('valida_hasta')->nullable();
            $table->foreignId('creada_por')->constrained('users');
            $table->timestamps();

            $table->index(['servicio_id', 'valida_desde', 'valida_hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_servicio');
    }
};
