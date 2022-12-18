<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorDerivacionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cor_derivaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('fecha_derivacion')->nullable();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->dateTime('fecha_rechazo')->nullable();
            $table->dateTime('fecha_anulado')->nullable();
            $table->dateTime('fecha_archivo')->nullable();
            $table->string('proveido');
            $table->unsignedBigInteger('tramite_id');
            $table->unsignedBigInteger('remitente_id')->nullable();
            $table->unsignedBigInteger('destinatario_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cor_derivaciones');
    }
}
