<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nac')->nullable();
            $table->string('CI',15)->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono_1',20)->nullable();
            $table->string('telefono_2',20)->nullable();
            $table->string('email_personal')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('adjunto')->nullable();
            $table->string('foto')->nullable();
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
        Schema::dropIfExists('personal');
    }
}
