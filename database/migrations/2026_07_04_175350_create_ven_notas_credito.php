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
        Schema::create('ven_notas_credito', function (Blueprint $table) {
            $table->id();
            
            $table->string('numero', 50)->unique();
            $table->foreignId('factura_id')->constrained('ven_facturas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('ven_clientes')->cascadeOnDelete();
            
            $table->date('fecha_emision');
            $table->enum('motivo', [
                'devolucion',
                'descuento',
                'error_factura',
                'otros'
            ])->default('devolucion');
            
            $table->decimal('monto', 18, 6);
            $table->string('moneda', 3)->default('BOB');
            
            $table->text('observaciones')->nullable();
            
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('factura_id');
            $table->index('cliente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ven_notas_credito');
    }
};
