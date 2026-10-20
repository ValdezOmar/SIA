<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_eventos_auditoria', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_evento', 40)->index();
            $table->string('nivel', 20)->index();
            $table->string('modelo')->nullable()->index();
            $table->string('tabla')->nullable()->index();
            $table->string('registro_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->string('ruta')->nullable();
            $table->string('metodo_http', 10)->nullable();
            $table->unsignedSmallInteger('codigo_http')->nullable()->index();
            $table->string('clase_excepcion')->nullable();
            $table->unsignedInteger('linea_excepcion')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('agente_usuario', 1000)->nullable();
            $table->text('resumen');
            $table->json('datos')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'tipo_evento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_eventos_auditoria');
    }
};
