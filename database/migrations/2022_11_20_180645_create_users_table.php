<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();

            // Campos de empleado

            $table->date('fecha_alta')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->date('fecha_cambio')->nullable();
            $table->boolean('activo')->nullable()->default(false);
            $table->string('telefono_int',20)->nullable();

            $table->unsignedBigInteger('personal_id')->unique()->nullable();
            $table->foreign('personal_id')
                        ->references('id')
                        ->on('personal')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');

            $table->unsignedBigInteger('cargo_id')->nullable();
            $table->foreign('cargo_id')
                        ->references('id')
                        ->on('cargos')
                        // ->onDelete('restrict')
                        ->onUpdate('cascade');
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
        Schema::dropIfExists('users');
    }
};
