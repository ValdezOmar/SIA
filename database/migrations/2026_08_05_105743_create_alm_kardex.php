<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_kardex', function (Blueprint $table) {
            $table->id();
            
            // ========== DATOS DEL ARTÍCULO ==========
            $table->foreignId('articulo_id')
                ->constrained('alm_articulos')
                ->cascadeOnDelete()
                ->comment('Artículo del movimiento');
            
            $table->foreignId('almacen_id')
                ->constrained('alm_almacenes')
                ->cascadeOnDelete()
                ->comment('Almacén donde ocurre el movimiento');
            
            $table->foreignId('ubicacion_id')
                ->nullable()
                ->constrained('alm_ubicaciones')
                ->nullOnDelete()
                ->comment('Ubicación específica dentro del almacén');
            
            // ========== DATOS DEL MOVIMIENTO ==========
            $table->enum('tipo_movimiento', [
                'compra',           // Entrada por compra
                'venta',            // Salida por venta
                'transferencia_entrada', // Entrada por transferencia
                'transferencia_salida',  // Salida por transferencia
                'ajuste_incremento', // Ajuste positivo
                'ajuste_decremento', // Ajuste negativo
                'devolucion_compra', // Devolución a proveedor
                'devolucion_venta',  // Devolución de cliente
                'produccion_entrada', // Entrada por producción
                'produccion_salida',  // Salida por producción
                'inventario_inicial', // Inventario inicial
                'ajuste_fisico',     // Ajuste por inventario físico
                'merma',             // Merma/rotura
                'despacho',          // Despacho a cliente
                'consignacion',      // Consignación
            ])->comment('Tipo de movimiento de inventario');
            
            $table->enum('direccion', ['entrada', 'salida'])
                ->comment('Dirección del movimiento: entrada o salida');
            
            // ========== CANTIDADES ==========
            $table->decimal('cantidad', 18, 6)
                ->comment('Cantidad del movimiento');
            
            $table->decimal('cantidad_anterior', 18, 6)
                ->default(0)
                ->comment('Saldo anterior antes del movimiento');
            
            $table->decimal('cantidad_posterior', 18, 6)
                ->default(0)
                ->comment('Saldo posterior después del movimiento');
            
            // ========== COSTOS ==========
            $table->decimal('costo_unitario', 18, 6)
                ->default(0)
                ->comment('Costo unitario del movimiento');
            
            $table->decimal('costo_total', 18, 6)
                ->default(0)
                ->comment('Costo total del movimiento (cantidad * costo_unitario)');
            
            $table->decimal('costo_promedio', 18, 6)
                ->default(0)
                ->comment('Costo promedio después del movimiento');
            
            $table->decimal('costo_acumulado', 18, 6)
                ->default(0)
                ->comment('Costo acumulado total del inventario');
            
            // ========== REFERENCIA A DOCUMENTOS ==========
            $table->string('documento_tipo', 50)
                ->comment('Tipo de documento origen: compra, venta, transferencia, etc.');
            
            $table->unsignedBigInteger('documento_id')
                ->comment('ID del documento origen');
            
            $table->string('documento_codigo', 50)
                ->nullable()
                ->comment('Código del documento origen (ej: OC-001, VTA-001)');
            
            $table->unsignedBigInteger('documento_detalle_id')
                ->nullable()
                ->comment('ID del detalle del documento origen');
            
            // ========== REFERENCIA A MOVIMIENTO RELACIONADO ==========
            $table->foreignId('movimiento_relacionado_id')
                ->nullable()
                ->comment('ID del movimiento relacionado (ej: transferencia_salida -> transferencia_entrada)');
            
            // ========== DATOS DE COSTO FIFO ==========
            $table->json('capas_fifo_consumidas')
                ->nullable()
                ->comment('Capas FIFO consumidas en este movimiento (para salidas)');
            
            $table->foreignId('capa_fifo_id')
                ->nullable()
                ->comment('ID de la capa FIFO creada (para entradas)');
            
            // ========== DATOS DE SERIES/LOTES ==========
            $table->json('series')
                ->nullable()
                ->comment('Números de serie involucrados');
            
            $table->json('lotes')
                ->nullable()
                ->comment('Números de lote involucrados');
            
            // ========== DATOS DE USUARIO ==========
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que realizó el movimiento');
            
            $table->foreignId('autorizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que autorizó el movimiento');
            
            // ========== ESTADO ==========
            $table->enum('estado', ['pendiente', 'confirmado', 'cancelado', 'anulado'])
                ->default('confirmado')
                ->comment('Estado del movimiento');
            
            // ========== FECHAS ==========
            $table->timestamp('fecha_movimiento')
                ->useCurrent()
                ->comment('Fecha del movimiento');
            
            $table->timestamp('fecha_contable')
                ->nullable()
                ->comment('Fecha contable del movimiento');
            
            // ========== INFORMACIÓN ADICIONAL ==========
            $table->string('motivo', 255)
                ->nullable()
                ->comment('Motivo del movimiento');
            
            $table->text('observaciones')
                ->nullable()
                ->comment('Observaciones adicionales');
            
            $table->json('datos_adicionales')
                ->nullable()
                ->comment('Datos adicionales en formato JSON');
            
            // ========== AUDITORÍA ==========
            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('conf_empresas')
                ->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            // ========== ÍNDICES ==========
            $table->index(['articulo_id', 'almacen_id', 'fecha_movimiento']);
            $table->index(['articulo_id', 'almacen_id', 'tipo_movimiento']);
            $table->index(['documento_tipo', 'documento_id']);
            $table->index(['articulo_id', 'direccion']);
            $table->index('fecha_movimiento');
            $table->index('estado');
            $table->index('fecha_contable');
            
            // Índices compuestos para reportes
            $table->index(['empresa_id', 'fecha_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_kardex');
    }
};