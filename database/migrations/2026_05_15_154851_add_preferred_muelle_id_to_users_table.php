<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_muelle_id')
                  ->nullable()
                  ->after('role')
                  ->constrained('muelles')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Muelle::class, 'preferred_muelle_id');
            $table->dropColumn('preferred_muelle_id');
        });
    }
};
