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
        Schema::create('hd_eventos', function (Blueprint $table) {
            $table->id();
            //Datos de origen y estado de ticket en bandeja
            $table->foreignId('hd_ticket_id')->constrained('hd_tickets')->cascadeOnDelete();
            $table->foreignId('remitente_id')->nullable()->constrained('rh_empleados')->nullOnDelete();//Remitente es el que creo o envio el ticket
            $table->foreignId('encargado_id')->nullable()->constrained('rh_empleados')->nullOnDelete();//Encargado es el destinatario del que creo el ticket y es el encargado de resolverlo, puede tener 2 estados Entrada y Pendiente
            $table->foreignId('destinatario_id')->nullable()->constrained('rh_empleados')->nullOnDelete();//Destinatario es a la persona que se le derivo el ticket y tien dos estados Salida o cerrado, si esta en estado salida se muestra en la entrada del Encargado 
           
            $table->foreignId('area_origen_id')->nullable()->constrained('conf_areas')->nullOnDelete();
            $table->foreignId('area_destino_id')->nullable()->constrained('conf_areas')->nullOnDelete();
            $table->enum('estado', ['entrada', 'pendiente', 'salida', 'cerrado'])->nullable();
            $table->dateTime('fecha_entrada')->nullable();
            $table->dateTime('fecha_recepcion')->nullable(); 
            $table->dateTime('fecha_salida')->nullable();
            $table->text('observaciones')->nullable(); //Observaciones de porque se deriva o se rechaza
            //Operaciones de bandeja          
            $table->text('descripcion')->nullable(); 
            $table->string('prioridad')->nullable();      //'baja', 'media', 'alta', 'urgente'      
            $table->string('adjunto')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_eventos');
    }
};