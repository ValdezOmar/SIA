<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conf_parametros', function (Blueprint $table): void {
            $table->string('color_secundario', 20)->nullable()->after('color_principal');
            $table->string('escala_interfaz', 5)->default('88%')->after('color_secundario');
            $table->string('estilo_login', 20)->default('cristal')->after('escala_interfaz');
        });
    }

    public function down(): void
    {
        Schema::table('conf_parametros', function (Blueprint $table): void {
            $table->dropColumn(['color_secundario', 'escala_interfaz', 'estilo_login']);
        });
    }
};
