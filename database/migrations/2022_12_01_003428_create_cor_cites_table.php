<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorCitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cor_cites', function (Blueprint $table) {
            $table->id();
            $table->string('cite');
            $table->string('asunto');

            $table->unsignedBigInteger('cargo_id');
            $table->foreign('cargo_id')
                        ->references('id')
                        ->on('cargos')
                        ->onDelete('restrict');

            $table->unsignedBigInteger('elaborador_id');
            $table->foreign('elaborador_id')
                        ->references('id')
                        ->on('users')
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
        Schema::dropIfExists('cor_cites');
    }
}
