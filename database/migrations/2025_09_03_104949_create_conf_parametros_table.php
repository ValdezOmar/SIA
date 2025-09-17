<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conf_parametros', function (Blueprint $table) {
            $table->id();
            // Logos y branding
            $table->string('logo_path', 255)->nullable();
            $table->string('favicon_path', 255)->nullable();
            $table->string('fondo_path', 255)->nullable(); //Fondo de login
            $table->string('color_principal', 20)->nullable();            

            // Integraciones externas
            $table->boolean('google_activo')->default(0);
            $table->string('google_client_id')->nullable();
            $table->string('google_client_secret')->nullable();
            $table->text('google_redirect_uri')->nullable();

            // Configuración interna
            $table->boolean('login_nativo')->default(1);
            $table->string('timezone', 100)->default('America/La_Paz');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conf_parametros');
    }
};