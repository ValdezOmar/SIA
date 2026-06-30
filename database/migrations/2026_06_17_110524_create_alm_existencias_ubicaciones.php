<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alm_existencias_ubicaciones', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('existencia_id')
                ->constrained('alm_existencias')
                ->cascadeOnDelete()
                ->comment('ID de la existencia');

            $table->foreignId('ubicacion_id')
                ->constrained('alm_ubicaciones')
                ->cascadeOnDelete()
                ->comment('ID de la ubicación');

            $table->decimal('cantidad', 18, 6)
                ->default(0)
                ->comment('Cantidad en esta ubicación');

            $table->timestamps();

            // Una existencia no puede tener la misma ubicación dos veces
            $table->unique(['existencia_id', 'ubicacion_id'], 'uk_existencia_ubicacion');

            // Índices para búsquedas
            $table->index(['existencia_id', 'ubicacion_id']);
            $table->index('cantidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alm_existencias_ubicaciones');
    }
};