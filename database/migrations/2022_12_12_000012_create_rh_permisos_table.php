<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRhPermisosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rh_permisos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('descripcion');
            $table->string('descripcion_rechazo')->nullable();
            $table->unsignedBigInteger('permisosable_id');
            $table->string('permisosable_type');
            $table->unsignedBigInteger('tipo_permiso_id');

            $table->index('permisosable_id');
            $table->index('permisosable_type');

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
        Schema::dropIfExists('rh_permisos');
    }
}
