<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('con_centros_costos', function (Blueprint $table) {
            $table->id();
            
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('conf_areas')
                ->nullOnDelete();
            
            $table->foreignId('responsable_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->enum('tipo', ['costo', 'ingreso', 'mixto'])->default('costo');
            $table->boolean('activo')->default(true);
            
            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('conf_empresas')
                ->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('area_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('con_centros_costos');
    }
};