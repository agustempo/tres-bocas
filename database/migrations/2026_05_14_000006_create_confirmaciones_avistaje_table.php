<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirmaciones_avistaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avistaje_id')->constrained('avistajes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['avistaje_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmaciones_avistaje');
    }
};
