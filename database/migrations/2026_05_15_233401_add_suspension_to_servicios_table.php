<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->boolean('suspendido')->default(false)->after('activo');
            $table->string('suspension_motivo')->nullable()->after('suspendido');
        });
    }

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn(['suspendido', 'suspension_motivo']);
        });
    }
};
