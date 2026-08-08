<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alm_movimientos_inventario', function (Blueprint $table) {
            // ✅ Agregar relación con kardex
            $table->foreignId('kardex_id')
                ->nullable()
                ->after('id')
                ->constrained('alm_kardex')
                ->nullOnDelete()
                ->comment('ID del registro en kardex');

            // ✅ Agregar relación con capas FIFO
            $table->foreignId('capa_costo_id')
                ->nullable()
                ->after('costo_total')
                ->constrained('alm_capas_costos')
                ->nullOnDelete()
                ->comment('Capa de costo FIFO utilizada (para salidas)');

            // ✅ Agregar tipo de documento más detallado
            $table->enum('tipo_documento', [
                'compra',
                'venta',
                'transferencia',
                'ajuste',
                'produccion',
                'devolucion_compra',
                'devolucion_venta',
                'inventario_inicial',
                'inventario_fisico',
                'merma'
            ])->nullable()->after('documento_tipo');

            // ✅ Agregar referencia al pedido/cotización
            $table->string('referencia', 100)
                ->nullable()
                ->after('documento_id')
                ->comment('Referencia adicional del documento');

            // ✅ Agregar campos de auditoría
            $table->foreignId('creado_por')
                ->nullable()
                ->after('observacion')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('autorizado_por')
                ->nullable()
                ->after('creado_por')
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('estado', ['pendiente', 'confirmado', 'cancelado'])
                ->default('confirmado')
                ->after('observacion');
        });
    }

    public function down(): void
    {
        Schema::table('alm_movimientos_inventario', function (Blueprint $table) {
            $table->dropForeign(['kardex_id']);
            $table->dropForeign(['capa_costo_id']);
            $table->dropForeign(['creado_por']);
            $table->dropForeign(['autorizado_por']);
            
            $table->dropColumn([
                'kardex_id',
                'capa_costo_id',
                'tipo_documento',
                'referencia',
                'creado_por',
                'autorizado_por',
                'estado'
            ]);
        });
    }
};