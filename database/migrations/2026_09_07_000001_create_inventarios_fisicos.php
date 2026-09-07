<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_inventarios_fisicos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->foreignId('empresa_id')->constrained('conf_empresas')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('conf_sucursales')->restrictOnDelete();
            $table->foreignId('almacen_id')->constrained('alm_almacenes')->restrictOnDelete();
            $table->date('fecha_programada');
            $table->string('estado', 20)->default('programado')->index();
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('iniciado_at')->nullable();
            $table->timestamp('cerrado_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'sucursal_id', 'fecha_programada'], 'inv_fisico_contexto');
        });
        Schema::create('alm_inventario_conteos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_fisico_id')->constrained('alm_inventarios_fisicos')->restrictOnDelete();
            $table->foreignId('articulo_id')->constrained('alm_articulos')->restrictOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->string('unidad')->nullable();
            $table->decimal('stock_sistema', 18, 6);
            $table->decimal('stock_reservado', 18, 6)->default(0);
            $table->decimal('cantidad_contada', 18, 6)->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->text('observaciones')->nullable();
            $table->text('codigo_leido')->nullable();
            $table->foreignId('contado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('contado_at')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->text('nota_revision')->nullable();
            $table->timestamps();
            $table->unique(['inventario_fisico_id', 'articulo_id'], 'inv_conteo_articulo_unique');
        });
        Schema::create('alm_inventario_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_fisico_id')->constrained('alm_inventarios_fisicos')->restrictOnDelete();
            $table->foreignId('conteo_id')->nullable()->constrained('alm_inventario_conteos')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('accion', 50);
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_inventario_eventos');
        Schema::dropIfExists('alm_inventario_conteos');
        Schema::dropIfExists('alm_inventarios_fisicos');
    }
};
