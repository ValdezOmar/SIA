<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rh_horarios_asistencia')) {
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
        }

        if (! Schema::hasTable('rh_asignaciones_horario_asistencia')) {
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

                $table->index(
                    ['empleado_id', 'fecha_inicio'],
                    'horario_asistencia_empleado_inicio_index'
                );
            });
        }
    }

    public function down(): void
    {
        // No se eliminan tablas en el rollback: esta migracion repara una
        // instalacion incompleta y las tablas pueden contener datos previos.
    }
};
