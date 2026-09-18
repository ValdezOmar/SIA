<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cmp_recepciones MODIFY estado ENUM('pendiente', 'listo', 'parcial', 'completada', 'rechazada') NOT NULL DEFAULT 'pendiente'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE cmp_recepciones SET estado = 'pendiente' WHERE estado = 'listo'");
            DB::statement("ALTER TABLE cmp_recepciones MODIFY estado ENUM('pendiente', 'parcial', 'completada', 'rechazada') NOT NULL DEFAULT 'pendiente'");
        }
    }
};
