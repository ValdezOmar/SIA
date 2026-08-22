<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmp_recepciones', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique();
            $table->foreignId('orden_compra_id')->constrained('cmp_ordenes_compra')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('cmp_proveedores')->cascadeOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('alm_almacenes')->nullOnDelete();

            $table->date('fecha_recepcion');
            $table->string('guia_remision', 50)->nullable();
            $table->string('transportista', 100)->nullable();

            $table->enum('estado', ['pendiente', 'parcial', 'completada', 'rechazada'])->default('pendiente');
            $table->timestamp('inventario_procesado_at')->nullable();

            $table->text('observaciones')->nullable();

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('orden_compra_id');
            $table->index('proveedor_id');
            $table->index('almacen_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_recepciones');
    }
};
