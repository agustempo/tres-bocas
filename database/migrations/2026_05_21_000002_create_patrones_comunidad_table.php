<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrones_comunidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Departure dock — either a real muelle or a community muelle (at least one set)
            $table->foreignId('muelle_id')
                  ->nullable()
                  ->constrained('muelles')
                  ->nullOnDelete();
            $table->foreignId('muelle_comunidad_id')
                  ->nullable()
                  ->constrained('muelles_comunidad')
                  ->nullOnDelete();

            $table->string('destino');               // e.g. "Tigre"
            $table->string('empresa')->nullable();   // e.g. "Interisleña"
            $table->time('hora_referencia');
            $table->unsignedSmallInteger('ventana_min')->default(20);
            $table->string('sentido')->default('vuelta'); // ida | vuelta

            // Recurrence: diario | lv | fds | unico
            $table->string('recurrencia')->default('lv');
            $table->date('fecha_unica')->nullable(); // only when recurrencia = 'unico'

            // Trust
            $table->unsignedSmallInteger('confirmaciones')->default(0);
            $table->boolean('verificado')->default(false); // auto-set at 5 confirmations

            $table->timestamps();

            $table->index(['muelle_id', 'hora_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrones_comunidad');
    }
};
