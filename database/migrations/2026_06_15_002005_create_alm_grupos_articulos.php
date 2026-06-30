<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_grupos_articulos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)
                ->unique()
                ->comment('Código único del grupo');

            $table->string('nombre', 255)
                ->comment('Nombre del grupo');

            $table->foreignId('grupo_padre_id')
                ->nullable()
                ->constrained('alm_grupos_articulos')
                ->nullOnDelete()
                ->comment('Grupo padre para jerarquía');

            //
            $table->foreignId('empresa_id')
                ->nullable()
               
                ->constrained('conf_empresas')
                ->nullOnDelete()
                ->comment('Empresa a la que pertenece');

            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si el grupo está activo');

            $table->timestamps();

            // Índices
            $table->index('activo');
            $table->index('grupo_padre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_grupos_articulos');
    }
};