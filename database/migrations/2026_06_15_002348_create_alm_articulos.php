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
        Schema::create('alm_articulos', function (Blueprint $table) {
            $table->id();
            //Datos generales
            $table->string('codigo', 50)->unique();

            $table->string('codigo_alterno', 50)
                ->nullable();

            $table->string('codigo_barras_principal', 100)
                ->nullable()
                ->index();

            $table->text('descripcion')->nullable();
            $table->text('marca')->nullable();

            $table->string('presentacion')
                ->nullable();

            $table->foreignId('fabricante_id')
                ->nullable()
                ->constrained('alm_fabricantes')
                ->nullOnDelete();

            $table->foreignId('grupo_articulo_id')
                ->nullable()
                ->constrained('alm_grupos_articulos')
                ->nullOnDelete();

            $table->foreignId('unidad_medida_id')
                ->nullable()
                ->constrained('alm_unidades_medida')
                ->nullOnDelete();

            $table->boolean('inventariable')
                ->default(true);

            $table->boolean('comprable')
                ->default(true);

            $table->boolean('vendible')
                ->default(true);

            $table->boolean('maneja_lotes')
                ->default(false);

            $table->boolean('maneja_series')
                ->default(false);

            $table->boolean('requiere_serie_en_salida')
                ->default(false);

            $table->enum('metodo_costo', [
                'promedio',
                'fifo',
                'estandar'
            ])->default('promedio');

            $table->decimal('costo_referencial', 18, 6) //Costo referencial para el articulo, puede ser el mismo que el precio base o un costo sugerido
                ->default(0);

            $table->decimal('precio_base', 18, 6) //Precio base para calcular precios de venta, puede ser el mismo que el costo referencial o un precio sugerido
                ->default(0);

            $table->decimal('comision', 18, 6)
                ->default(0);

            $table->text('foto_catalogo')
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->foreignId('empresa_id')
                ->nullable()
                
                ->constrained('conf_empresas')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alm_articulos');
    }
};
