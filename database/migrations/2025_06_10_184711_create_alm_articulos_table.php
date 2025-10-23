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
            //Datos traidos mediante consulta    desde simec y sap      
            $table->string('codigo')->nullable()->index(); // Código único del producto            
            $table->string('descripcion')->nullable(); // Descripción del artículo            
            $table->string('presentacion')->nullable(); // Presentación del producto (puede ser 'S/N' si no hay)            
            $table->string('unidad')->nullable(); // Unidad de medida (puede ser 'S/N' si no hay)            
            $table->string('codigo_alterno')->nullable(); // Código alterno (puede ser 'S/N' si no hay)  
            $table->string('proveedor')->nullable();
            $table->integer('cod_almacen')->nullable(); // Código del almacén (0 si no hay)           
            $table->string('nombre_almacen')->nullable(); // Nombre del almacén ('SALDO' si no hay)
            $table->string('lote')->nullable(); // Lote del producto ('S/L' si no hay)             
            $table->date('fecha_ven')->nullable(); // Fecha de vencimiento (nullable)            
            $table->integer('saldo_actual')->nullable(); // Saldo actual ajustado

            //Datos personalizados para el sistema            
            $table->string('empresa')->nullable(); //Emepresa de referecnia de origen de datos  
            $table->string('sn_qr')->nullable(); //Codigo qr oserial asociado 
            $table->decimal('precio', 8, 2)->nullable();
            $table->decimal('comision', 8, 2)->nullable(); //Comision de los vendedores Hasta 999,999.99 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};