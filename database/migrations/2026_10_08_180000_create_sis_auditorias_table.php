<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sis_auditorias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('registrado_at')->index();
            $table->uuid('correlacion_id')->nullable()->index();
            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->string('origen', 30);
            $table->string('categoria', 40)->index();
            $table->string('evento', 100)->index();
            $table->string('nivel', 20)->index();
            $table->string('entidad')->nullable();
            $table->string('entidad_id', 100)->nullable()->index();
            $table->string('tabla', 100)->nullable();
            $table->string('descripcion', 500);
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();
            $table->json('contexto')->nullable();
            $table->index(['empresa_id', 'sucursal_id', 'registrado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sis_auditorias');
    }
};
