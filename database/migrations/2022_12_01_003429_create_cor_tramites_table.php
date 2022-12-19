<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Facades\Schema;

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
            $table->id();
            $table->unsignedBigInteger('remitente');
            $table->foreign('remitente')
                        ->references('id')
                        ->on('users');
                        // ->onDelete('restrict');

            $table->unsignedBigInteger('destinatario');
            $table->foreign('destinatario')
                        ->references('id')
                        ->on('users');
                        // ->onDelete('restrict');

            $table->string('proveido');
            $table->dateTime('fecha_recepcion')->nullable();
            $table->dateTime('fecha_derivacion')->nullable();
            $table->dateTime('fecha_rechazo')->nullable();
            $table->dateTime('fecha_anulado')->nullable();
            $table->dateTime('fecha_archivo')->nullable();

            $table->unsignedBigInteger('estado');
            $table->foreign('estado')
                        ->references('id')
                        ->on('cor_estados')
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
        Schema::dropIfExists('cor_tramites');
    }
}
