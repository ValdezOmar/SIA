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
        Schema::create('ven_clientes', function (Blueprint $table) {
            $table->id();

            // Datos básicos
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('razon_social', 255)->nullable();
            $table->string('ci/nit', 50)->nullable();
            
            // Contacto
            $table->string('telefono', 50)->nullable();
            $table->string('celular', 50)->nullable();
            $table->string('correo', 255)->nullable();
            $table->text('direccion')->nullable();

            // Contacto de referencia
            $table->string('contacto_nombre', 255)->nullable();
            $table->string('contacto_telefono', 50)->nullable();
            $table->string('contacto_correo', 255)->nullable();

            // Datos comerciales
            $table->enum('tipo_cliente', [
                'persona_natural',
                'empresa',
                'gobierno',
                'extranjero'
            ])->default('persona_natural');

            // Categoría del cliente
            $table->enum('categoria', [
                'regular',
                'mayorista',
                'minorista',
                'vip',
                'revendedor'
            ])->default('regular');

            // Condiciones de pago
            $table->string('condicion_pago', 100)->nullable();

            // Descuentos
            $table->decimal('descuento_general', 18, 6)->default(0);
            $table->decimal('descuento_especial', 18, 6)->default(0);

            // Lista de precios
            $table->foreignId('lista_precio_id')
                ->nullable()
                ->constrained('alm_listas_precios')
                ->nullOnDelete();

            // Ubicación
            $table->string('ciudad')->nullable();
            $table->string('zona', 100)->nullable();

            // Estado
            $table->boolean('activo')->default(true);
            $table->boolean('bloqueado')->default(false);

            // Observaciones
            $table->text('motivo_bloqueo')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('ci/nit');
            $table->index('tipo_cliente');
            $table->index('categoria');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ven_clientes');
    }
};
