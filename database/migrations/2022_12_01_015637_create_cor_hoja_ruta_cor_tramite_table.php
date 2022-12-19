<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorHojaRutaCorTramiteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cor_hoja_ruta_cor_tramite', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cor_hoja_ruta_id');
            $table->foreign('cor_hoja_ruta_id')
                        ->references('id')
                        ->on('cor_hoja_rutas')
                        ->onDelete('restrict');

            $table->unsignedBigInteger('cor_tramite_id');
            $table->foreign('cor_tramite_id')
                        ->references('id')
                        ->on('cor_tramites')
                        ->onDelete('restrict');
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
        Schema::dropIfExists('cor_hoja_ruta_tramites');
    }
}
