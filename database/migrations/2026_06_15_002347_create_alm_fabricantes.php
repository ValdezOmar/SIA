<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_fabricantes', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)
                ->unique()
                ->comment('Código único del fabricante');

            $table->string('nombre', 255)
                ->comment('Nombre del fabricante');

            $table->string('nombre_comercial', 255)
                ->nullable()
                ->comment('Nombre comercial o marca');

            $table->string('sitio_web', 255)
                ->nullable()
                ->comment('Sitio web oficial');

            $table->string('correo', 255)
                ->nullable()
                ->comment('Correo electrónico de contacto');

            $table->string('telefono', 50)
                ->nullable()
                ->comment('Teléfono de contacto');

            $table->text('direccion')
                ->nullable()
                ->comment('Dirección física');

            $table->text('observaciones')
                ->nullable()
                ->comment('Notas adicionales');

            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si el fabricante está activo');

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
        Schema::dropIfExists('alm_fabricantes');
    }
};