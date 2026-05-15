<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avistajes', function (Blueprint $table) {
            $table->foreignId('patron_id')
                  ->nullable()
                  ->after('servicio_id')
                  ->constrained('patrones')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('avistajes', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Patron::class);
            $table->dropColumn('patron_id');
        });
    }
};
