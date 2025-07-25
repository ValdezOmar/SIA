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
        Schema::create('alm_inventarios', function (Blueprint $table) {
            $table->id();
            //Datos traidos mediante cnsulta          
            $table->string('codigo')->nullable(); // Código único del producto            
            $table->string('descripcion')->nullable(); // Descripción del artículo            
            $table->string('presentacion')->nullable(); // Presentación del producto            
            $table->string('unidad')->nullable(); // Unidad de medida (puede ser 'S/N' si no hay)            
            $table->string('codigo_alterno')->nullable(); // Código alterno (puede ser 'S/N' si no hay)            
            $table->integer('cod_almacen')->nullable(); // Código del almacén (0 si no hay)           
            $table->string('nombre_almacen')->nullable(); // Nombre del almacén ('SALDO' si no hay)
            $table->string('lote')->nullable(); // Lote del producto ('S/L' si no hay)             
            $table->date('fecha_ven')->nullable(); // Fecha de vencimiento (nullable) 
            $table->string('sn_qr')->nullable();//Codigo qr oserial asociado 
            $table->string('empresa')->nullable();//Emepresa de referecnia de origen de datos  
            $table->integer('saldo_actual')->nullable(); // Saldo actual ajustado 

            //Datos correctos para rectificacion
            $table->string('codigo_correcto')->nullable(); // Código único del producto            
            $table->string('descripcion_correcto')->nullable(); // Descripción del artículo            
            $table->string('presentacion_correcto')->nullable(); // Presentación del producto            
            $table->string('unidad_correcto')->nullable(); // Unidad de medida (puede ser 'S/N' si no hay)            
            $table->string('codigo_alterno_correcto')->nullable(); // Código alterno (puede ser 'S/N' si no hay)            
            $table->integer('cod_almacen_correcto')->nullable(); // Código del almacén (0 si no hay)           
            $table->string('nombre_almacen_correcto')->nullable(); // Nombre del almacén ('SALDO' si no hay)
            $table->string('lote_correcto')->nullable(); // Lote del producto ('S/L' si no hay)             
            $table->date('fecha_ven_correcto')->nullable(); // Fecha de vencimiento (nullable) 
            $table->string('sn_qr_correcto')->nullable();//Codigo qr oserial asociado 
            $table->string('empresa_correcto')->nullable();//Emepresa de referecnia de origen de datos  
           
            $table->integer('saldo_contado')->nullable(); // Saldo actual contado
            $table->string('observacion')->nullable();
             
            //Datos personalizados para el sistema
            $table->dateTime('fecha_conteo_inventario')->nullable(); // Fecha de conteo realizado 
            $table->boolean('activo')->nullable(); 
            $table->string('usuario')->nullable();         
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};