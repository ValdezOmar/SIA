<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRhTipoPermisosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rh_tipo_permisos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre_permiso');
            $table->string('tipo_permiso');
            $table->time('tiempo_permitido');
            $table->text('descripcion_permiso');

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
        Schema::dropIfExists('h_tipo_permisos');
    }
}
