<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignsToCorDerivacionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cor_derivaciones', function (Blueprint $table) {
            $table
                ->foreign('tramite_id')
                ->references('id')
                ->on('cor_tramites')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('remitente_id')
                ->references('id')
                ->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('destinatario_id')
                ->references('id')
                ->on('users')
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
        Schema::table('cor_derivaciones', function (Blueprint $table) {
            $table->dropForeign(['tramite_id']);
            $table->dropForeign(['remitente_id']);
            $table->dropForeign(['destinatario_id']);
        });
    }
}
