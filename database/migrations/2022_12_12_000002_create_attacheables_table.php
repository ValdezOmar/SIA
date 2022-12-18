<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttacheablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attacheables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('attacheableable_id');
            $table->string('attacheableable_type');

            $table->index('attacheableable_id');
            $table->index('attacheableable_type');

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
        Schema::dropIfExists('attacheables');
    }
}
