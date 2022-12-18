<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCorTramitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cor_tramites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('recepcion_user_id');
            $table->string('hoja_ruta', 50);
            $table->dateTime('fecha_ingreso');
            $table->boolean('hr_ext');
            $table->unsignedBigInteger('remitente_externo_id');
            $table->text('asunto_ext')->nullable();
            $table->string('cite_ext')->nullable();
            $table->unsignedBigInteger('cite_interno_id')->nullable();
            $table->unsignedBigInteger('asunto_int')->nullable();
            $table->unsignedBigInteger('tipo_documento_id')->nullable();
            $table->string('estado', 5);
            $table->boolean('fusionado')->nullable();
            $table->string('hr_fusionado')->nullable();
            $table->unsignedBigInteger('remitente_interno_id');

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
        Schema::dropIfExists('cor_tramites');
    }
}
