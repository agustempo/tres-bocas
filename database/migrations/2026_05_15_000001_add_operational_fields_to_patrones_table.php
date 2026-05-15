<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrones', function (Blueprint $table) {
            // Fuente del dato: oficial (empresa), comunidad (vecinos), estimado (calculado por offset)
            $table->string('fuente')->default('estimado')->after('activo');
            // Visibilidad pública
            $table->string('visibilidad')->default('publico')->after('fuente');
            // URL/referencia de donde viene el horario (ej: imagen, planilla)
            $table->string('fuente_url')->nullable()->after('visibilidad');
            // Última vez que un admin confirmó que este horario sigue vigente
            $table->timestamp('validado_at')->nullable()->after('fuente_url');
            // Notas internas de administración
            $table->text('notas_admin')->nullable()->after('validado_at');
            // Tipo de día: 'lv'|'sabado'|'domingo'|'todos' — reemplaza el modelo de 5 filas por L-V
            // Nullable para compatibilidad con datos existentes (dia_semana sigue siendo la fuente)
            $table->string('tipo_dia')->nullable()->after('notas_admin');
        });
    }

    public function down(): void
    {
        Schema::table('patrones', function (Blueprint $table) {
            $table->dropColumn(['fuente', 'visibilidad', 'fuente_url', 'validado_at', 'notas_admin', 'tipo_dia']);
        });
    }
};
