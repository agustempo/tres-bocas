<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('muelle_id')->constrained('muelles')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana')->nullable(); // 0=domingo..6=sábado, null=todos
            $table->time('hora_referencia');
            $table->unsignedSmallInteger('ventana_min')->default(30); // ± minutos
            $table->string('sentido')->default('ambos'); // ida | vuelta | ambos
            $table->string('temporada')->default('todo'); // verano | invierno | todo
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrones');
    }
};
