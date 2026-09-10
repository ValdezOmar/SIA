<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('conf_empresas', 'logo_path')) {
            Schema::table('conf_empresas', fn (Blueprint $table) => $table->string('logo_path')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('conf_empresas', 'logo_path')) {
            Schema::table('conf_empresas', fn (Blueprint $table) => $table->dropColumn('logo_path'));
        }
    }
};
