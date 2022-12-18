<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignsToCorTramitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cor_tramites', function (Blueprint $table) {
            $table
                ->foreign('recepcion_user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('remitente_externo_id')
                ->references('id')
                ->on('cor_remitente_externos')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('cite_interno_id')
                ->references('id')
                ->on('cor_cite_internos')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('asunto_int')
                ->references('id')
                ->on('cor_cite_internos')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('tipo_documento_id')
                ->references('id')
                ->on('cor_tipo_documentos')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('remitente_interno_id')
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
        Schema::table('cor_tramites', function (Blueprint $table) {
            $table->dropForeign(['recepcion_user_id']);
            $table->dropForeign(['remitente_externo_id']);
            $table->dropForeign(['cite_interno_id']);
            $table->dropForeign(['asunto_int']);
            $table->dropForeign(['tipo_documento_id']);
            $table->dropForeign(['remitente_interno_id']);
        });
    }
}
