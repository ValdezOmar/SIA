<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_horarios_asistencia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->json('dias_laborales');
            $table->time('hora_entrada');
            $table->unsignedSmallInteger('tolerancia_minutos')->default(0);
            $table->time('hora_omision')->nullable();
            $table->time('hora_inicio_almuerzo')->nullable();
            $table->time('hora_fin_almuerzo')->nullable();
            $table->time('hora_salida')->nullable();
            $table->boolean('requiere_marcacion_almuerzo')->default(false);
            $table->boolean('activo')->default(true);
            $table->boolean('predeterminado')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('rh_asignaciones_horario_asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_asistencia_id')
                ->constrained('rh_horarios_asistencia')
                ->cascadeOnDelete();
            $table->foreignId('empleado_id')
                ->constrained('rh_empleados')
                ->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['empleado_id', 'fecha_inicio'], 'horario_asistencia_empleado_inicio_index');
        });

        DB::table('rh_horarios_asistencia')->insert([
            'nombre' => 'Horario administrativo',
            'codigo' => 'ADMINISTRATIVO',
            'dias_laborales' => json_encode([1, 2, 3, 4, 5]),
            'hora_entrada' => '08:30:00',
            'tolerancia_minutos' => 5,
            'hora_omision' => '10:00:00',
            'hora_inicio_almuerzo' => '12:30:00',
            'hora_fin_almuerzo' => '14:30:00',
            'hora_salida' => '18:30:00',
            'requiere_marcacion_almuerzo' => false,
            'activo' => true,
            'predeterminado' => true,
            'observaciones' => 'Configuración inicial editable. Asigne turnos específicos a los empleados cuando corresponda.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_asignaciones_horario_asistencia');
        Schema::dropIfExists('rh_horarios_asistencia');
    }
};
