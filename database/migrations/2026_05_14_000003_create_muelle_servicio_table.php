<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muelle_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('muelle_id')->constrained('muelles')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->nullable();
            $table->string('sentido')->default('ambos'); // ida | vuelta | ambos
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['muelle_id', 'servicio_id', 'sentido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muelle_servicio');
    }
};
