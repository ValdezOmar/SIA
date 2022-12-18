<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignsToHrCargosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rh_cargos', function (Blueprint $table) {
            $table
                ->foreign('area_id')
                ->references('id')
                ->on('rh_areas')
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
        Schema::table('hr_cargos', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
        });
    }
}
