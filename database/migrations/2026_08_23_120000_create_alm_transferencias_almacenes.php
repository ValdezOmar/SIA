<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_transferencias_almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->foreignId('almacen_origen_id')->constrained('alm_almacenes')->restrictOnDelete();
            $table->foreignId('almacen_destino_id')->constrained('alm_almacenes')->restrictOnDelete();
            $table->foreignId('receptor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recibido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'en_transito', 'recibida', 'rechazada'])->default('borrador');
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_recepcion')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->timestamps();
            $table->index(['estado', 'almacen_destino_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_transferencias_almacenes');
    }
};
