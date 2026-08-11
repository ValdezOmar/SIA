<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_proyectos', function (Blueprint $table) {
            $table->id();
            
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            
            $table->enum('estado', ['planeacion', 'activo', 'pausado', 'finalizado', 'cancelado'])->default('planeacion');
            
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('ven_clientes')->nullOnDelete();
            
            $table->decimal('presupuesto', 18, 6)->default(0);
            $table->decimal('gastado', 18, 6)->default(0);
            
            $table->foreignId('empresa_id')->nullable()->constrained('conf_empresas')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('estado');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('con_proyectos');
    }
};