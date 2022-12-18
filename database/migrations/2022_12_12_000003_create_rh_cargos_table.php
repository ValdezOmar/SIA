<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRhCargosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rh_cargos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre_cargo');
            $table->text('descripcion_funciones')->nullable();
            $table->unsignedBigInteger('area_id');

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
        Schema::dropIfExists('hr_cargos');
    }
}
