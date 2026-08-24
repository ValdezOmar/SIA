<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ven_clientes', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('razon_social', 255)->nullable();
            $table->string('ci/nit', 50)->nullable();

            // Contexto comercial
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            $table->enum('tipo_cliente', ['persona_natural', 'empresa', 'gobierno', 'extranjero'])->default('persona_natural');
            $table->enum('categoria', ['regular', 'mayorista', 'minorista', 'vip', 'revendedor'])->default('regular');
            $table->string('condicion_pago', 100)->nullable();
            $table->foreignId('lista_precio_id')->nullable()->constrained('alm_listas_precios')->nullOnDelete();
            $table->decimal('descuento_general', 18, 6)->default(0);
            $table->decimal('descuento_especial', 18, 6)->default(0);

            // Contacto y ubicación
            $table->string('telefono', 50)->nullable();
            $table->string('celular', 50)->nullable();
            $table->string('correo', 255)->nullable();
            $table->text('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('zona', 100)->nullable();
            $table->string('contacto_nombre', 255)->nullable();
            $table->string('contacto_telefono', 50)->nullable();
            $table->string('contacto_correo', 255)->nullable();

            // Estado y auditoría
            $table->boolean('activo')->default(true);
            $table->boolean('bloqueado')->default(false);
            $table->text('motivo_bloqueo')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Índices de búsqueda y filtros
            $table->index('ci/nit');
            $table->index('tipo_cliente');
            $table->index('categoria');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ven_clientes');
    }
};
