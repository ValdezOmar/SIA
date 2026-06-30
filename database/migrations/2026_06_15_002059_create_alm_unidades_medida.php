<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_unidades_medida', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)
                ->unique()
                ->comment('Código único de la unidad');

            $table->string('nombre', 255)
                ->comment('Nombre de la unidad de medida');

            $table->string('abreviatura', 20)
                ->comment('Abreviatura de la unidad');

            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si la unidad está activa');

            //
            $table->foreignId('empresa_id')
                ->nullable()                
                ->constrained('conf_empresas')
                ->nullOnDelete()
                ->comment('Empresa a la que pertenece');

            $table->timestamps();

            // Índices
            $table->index('activo');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_unidades_medida');
    }
};