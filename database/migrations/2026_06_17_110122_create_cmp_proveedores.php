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
        Schema::create('cmp_proveedores', function (Blueprint $table) {
            $table->id();

            // ========== DATOS BÁSICOS ==========
            $table->string('codigo', 50)
                ->unique()
                ->comment('Código único del proveedor');

            $table->string('nombre', 255)
                ->comment('Nombre o razón social del proveedor');

            $table->string('nit', 50)
                ->nullable()
                ->comment('NIT / RUC del proveedor');

            $table->string('telefono', 255)
                ->nullable()
                ->comment('Teléfono de contacto');

            $table->string('correo', 255)
                ->nullable()
                ->comment('Correo electrónico');

            $table->text('direccion')
                ->nullable()
                ->comment('Dirección física');

            // ========== CAMPOS DE CONTACTO ==========
            // NOTA: SIN "after" en CREATE TABLE
            $table->string('contacto_nombre', 255)
                ->nullable()
                ->comment('Nombre de la persona de contacto');

            $table->string('contacto_cargo', 255)
                ->nullable()
                ->comment('Cargo de la persona de contacto');

            $table->string('contacto_telefono', 255)
                ->nullable()
                ->comment('Teléfono directo de contacto');

            $table->string('contacto_correo', 255)
                ->nullable()
                ->comment('Correo electrónico directo de contacto');

            // ========== ESTADO ==========
            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si el proveedor está activo');

            // ========== CAMPOS COMERCIALES ==========
            // NOTA: SIN "after" en CREATE TABLE
            $table->enum('tipo_proveedor', ['nacional', 'internacional', 'local'])
                ->default('nacional')
                ->comment('Clasificación del proveedor');

            $table->integer('calificacion')
                ->nullable()
                ->comment('Calificación del proveedor (1-5)');

            $table->integer('tiempo_entrega')
                ->nullable()
                ->comment('Días promedio de entrega');

            $table->string('condiciones_pago', 255)
                ->nullable()
                ->comment('Condiciones de pago acordadas');

            $table->text('observaciones')
                ->nullable()
                ->comment('Notas adicionales');

            // ========== EMPRESA ==========
            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('conf_empresas')
                ->nullOnDelete()
                ->comment('Empresa a la que pertenece');

            $table->timestamps();

            // ========== ÍNDICES ==========
            $table->index('tipo_proveedor');
            $table->index('calificacion');
            $table->index('activo');
            $table->index('nit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmp_proveedores');
    }
};