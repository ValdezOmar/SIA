<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cmp_recepciones', function (Blueprint $table): void {
            $table->string('moneda', 3)->default('BOB')->after('almacen_id');
            $table->decimal('tasa_cambio', 18, 6)->default(1)->after('moneda');
        });

        Schema::table('cmp_recepciones_detalle', function (Blueprint $table): void {
            $table->decimal('costo_unitario_base', 18, 6)->default(0)->after('costo_unitario');
            $table->decimal('gasto_adicional_base', 18, 6)->default(0)->after('costo_unitario_base');
        });

        Schema::create('cmp_gastos_adicionales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('cmp_recepciones')->cascadeOnDelete();
            $table->string('tipo', 50)->default('otro');
            $table->string('descripcion', 255);
            $table->string('moneda', 3)->default('BOB');
            $table->decimal('tasa_cambio', 18, 6)->default(1);
            $table->decimal('monto', 18, 6)->default(0);
            $table->decimal('monto_base', 18, 6)->default(0)->comment('Importe convertido a BOB para costo y contabilidad.');
            $table->boolean('capitalizable')->default(true)->comment('Suma al costo de inventario.');
            $table->string('criterio_prorrateo', 20)->default('valor');
            $table->string('documento_referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['recepcion_id', 'capitalizable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmp_gastos_adicionales');
        Schema::table('cmp_recepciones_detalle', fn (Blueprint $table) => $table->dropColumn(['costo_unitario_base', 'gasto_adicional_base']));
        Schema::table('cmp_recepciones', fn (Blueprint $table) => $table->dropColumn(['moneda', 'tasa_cambio']));
    }
};