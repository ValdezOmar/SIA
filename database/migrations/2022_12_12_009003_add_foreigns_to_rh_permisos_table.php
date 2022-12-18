<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignsToRhPermisosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rh_permisos', function (Blueprint $table) {
            $table
                ->foreign('tipo_permiso_id')
                ->references('id')
                ->on('rh_tipo_permisos')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rh_permisos', function (Blueprint $table) {
            $table->dropForeign(['tipo_permiso_id']);
        });
    }
}
