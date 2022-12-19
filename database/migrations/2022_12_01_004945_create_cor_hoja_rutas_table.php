<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorHojaRutasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cor_hoja_rutas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hoja_ruta');
            $table->string('cite_externo')->nullable();
            $table->boolean('hr_externo');
            $table->dateTime('fecha_ingreso');
            $table->string('asunto');

            $table->unsignedBigInteger('cite_interno_id')->nullable()->unique();
            $table->foreign('cite_interno_id')
                        ->references('id')
                        ->on('cor_cites')
                        ->onDelete('restrict');

            $table->unsignedBigInteger('tipo_proceso_id');
            $table->foreign('tipo_proceso_id')
                        ->references('id')
                        ->on('cor_tipo_procesos')
                        ->onDelete('restrict');

            $table->unsignedBigInteger('remitente_interno_id')->nullable();
            $table->foreign('remitente_interno_id')
                        ->references('id')
                        ->on('users');

            $table->unsignedBigInteger('remitente_externo_id')->nullable();
            $table->foreign('remitente_externo_id')
                        ->references('id')
                        ->on('cor_remitente_externos')                        ;

            // $table->unsignedBigInteger('adjunto_id');
            // $table->foreign('remitente_externo_id')
            //             ->references('id')
            //             ->on('cor_remitente_externos')
            //             ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cor_hoja_rutas');
    }
}
