<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avistajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('muelle_id')->constrained('muelles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo'); // paso | embarco | no_paro | cancelado | demorado
            $table->timestamp('hora_evento');
            $table->string('sentido')->nullable(); // ida | vuelta
            $table->text('notas')->nullable();
            $table->decimal('nivel_marea', 4, 2)->nullable();
            $table->unsignedSmallInteger('viento_kmh')->nullable();
            $table->string('condicion_clima')->nullable();
            $table->unsignedInteger('confirmaciones')->default(0);
            $table->timestamps();

            $table->index(['muelle_id', 'servicio_id', 'hora_evento']);
            $table->index('hora_evento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avistajes');
    }
};
