<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TRIGGERS = [
        'alm_precios_articulos_audit_insert',
        'alm_precios_articulos_audit_update',
        'alm_precios_articulos_audit_delete',
        'alm_articulos_audit_precios_delete',
        'alm_listas_precios_audit_precios_delete',
    ];

    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql' => $this->createMysqlTriggers(),
            'sqlite' => $this->createSqliteTriggers(),
            default => throw new RuntimeException('La auditoría de precios requiere triggers para el motor de base de datos configurado.'),
        };
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        foreach (self::TRIGGERS as $trigger) {
            $sql = $driver === 'mysql'
                ? "DROP TRIGGER IF EXISTS `{$trigger}`"
                : "DROP TRIGGER IF EXISTS {$trigger}";

            DB::unprepared($sql);
        }
    }

    private function createMysqlTriggers(): void
    {
        $this->dropExistingTriggers();

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_precios_articulos_audit_insert
            AFTER INSERT ON alm_precios_articulos
            FOR EACH ROW
            INSERT INTO alm_precios_articulos_historial (
                precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                lista_precio_nuevo_id, lista_precio_nuevo_codigo, lista_precio_nuevo_nombre,
                lista_precio_nuevo_moneda, precio_nuevo, usuario_id, usuario_nombre,
                registrado_en, created_at, updated_at
            )
            SELECT NEW.id, NEW.articulo_id, a.codigo, COALESCE(a.nombre_comercial, a.descripcion), 'creado',
                NEW.lista_precio_id, l.codigo, l.nombre, l.moneda, NEW.precio,
                NULL, 'Sistema (BD)',
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM alm_articulos a
            LEFT JOIN alm_listas_precios l ON l.id = NEW.lista_precio_id
            WHERE a.id = NEW.articulo_id
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_precios_articulos_audit_update
            AFTER UPDATE ON alm_precios_articulos
            FOR EACH ROW
            BEGIN
                IF NOT (OLD.precio <=> NEW.precio)
                    OR NOT (OLD.lista_precio_id <=> NEW.lista_precio_id)
                    OR NOT (OLD.articulo_id <=> NEW.articulo_id) THEN
                    INSERT INTO alm_precios_articulos_historial (
                        precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                        lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                        lista_precio_anterior_moneda, lista_precio_nuevo_id, lista_precio_nuevo_codigo,
                        lista_precio_nuevo_nombre, lista_precio_nuevo_moneda, precio_anterior, precio_nuevo,
                        usuario_id, usuario_nombre, registrado_en, created_at, updated_at
                    )
                    SELECT NEW.id, NEW.articulo_id, a.codigo, COALESCE(a.nombre_comercial, a.descripcion), 'actualizado',
                        OLD.lista_precio_id, lo.codigo, lo.nombre, lo.moneda,
                        NEW.lista_precio_id, ln.codigo, ln.nombre, ln.moneda,
                        OLD.precio, NEW.precio, NULL, 'Sistema (BD)',
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    FROM alm_articulos a
                    LEFT JOIN alm_listas_precios lo ON lo.id = OLD.lista_precio_id
                    LEFT JOIN alm_listas_precios ln ON ln.id = NEW.lista_precio_id
                    WHERE a.id = NEW.articulo_id;
                END IF;
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_precios_articulos_audit_delete
            AFTER DELETE ON alm_precios_articulos
            FOR EACH ROW
            INSERT INTO alm_precios_articulos_historial (
                precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                lista_precio_anterior_moneda, precio_anterior, usuario_id, usuario_nombre,
                registrado_en, created_at, updated_at
            )
            SELECT OLD.id, OLD.articulo_id, a.codigo, COALESCE(a.nombre_comercial, a.descripcion), 'eliminado',
                OLD.lista_precio_id, l.codigo, l.nombre, l.moneda, OLD.precio,
                NULL, 'Sistema (BD)',
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM alm_articulos a
            LEFT JOIN alm_listas_precios l ON l.id = OLD.lista_precio_id
            WHERE a.id = OLD.articulo_id
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_articulos_audit_precios_delete
            BEFORE DELETE ON alm_articulos
            FOR EACH ROW
            INSERT INTO alm_precios_articulos_historial (
                precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                lista_precio_anterior_moneda, precio_anterior, usuario_id, usuario_nombre,
                registrado_en, created_at, updated_at
            )
            SELECT p.id, p.articulo_id, OLD.codigo, COALESCE(OLD.nombre_comercial, OLD.descripcion), 'eliminado',
                p.lista_precio_id, l.codigo, l.nombre, l.moneda, p.precio,
                NULL, 'Sistema (BD)',
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM alm_precios_articulos p
            LEFT JOIN alm_listas_precios l ON l.id = p.lista_precio_id
            WHERE p.articulo_id = OLD.id
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_listas_precios_audit_precios_delete
            BEFORE DELETE ON alm_listas_precios
            FOR EACH ROW
            INSERT INTO alm_precios_articulos_historial (
                precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                lista_precio_anterior_moneda, precio_anterior, usuario_id, usuario_nombre,
                registrado_en, created_at, updated_at
            )
            SELECT p.id, p.articulo_id, a.codigo, COALESCE(a.nombre_comercial, a.descripcion), 'eliminado',
                OLD.id, OLD.codigo, OLD.nombre, OLD.moneda, p.precio,
                NULL, 'Sistema (BD)',
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM alm_precios_articulos p
            JOIN alm_articulos a ON a.id = p.articulo_id
            WHERE p.lista_precio_id = OLD.id
            SQL);
    }

    private function createSqliteTriggers(): void
    {
        $this->dropExistingTriggers();

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_precios_articulos_audit_insert
            AFTER INSERT ON alm_precios_articulos
            BEGIN
                INSERT INTO alm_precios_articulos_historial (
                    precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                    lista_precio_nuevo_id, lista_precio_nuevo_codigo, lista_precio_nuevo_nombre,
                    lista_precio_nuevo_moneda, precio_nuevo, usuario_nombre, registrado_en, created_at, updated_at
                ) VALUES (
                    NEW.id, NEW.articulo_id,
                    (SELECT codigo FROM alm_articulos WHERE id = NEW.articulo_id),
                    (SELECT COALESCE(nombre_comercial, descripcion) FROM alm_articulos WHERE id = NEW.articulo_id),
                    'creado', NEW.lista_precio_id,
                    (SELECT codigo FROM alm_listas_precios WHERE id = NEW.lista_precio_id),
                    (SELECT nombre FROM alm_listas_precios WHERE id = NEW.lista_precio_id),
                    (SELECT moneda FROM alm_listas_precios WHERE id = NEW.lista_precio_id),
                    NEW.precio, 'Sistema (BD)', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                );
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_precios_articulos_audit_update
            AFTER UPDATE ON alm_precios_articulos
            WHEN OLD.precio IS NOT NEW.precio
                OR OLD.lista_precio_id IS NOT NEW.lista_precio_id
                OR OLD.articulo_id IS NOT NEW.articulo_id
            BEGIN
                INSERT INTO alm_precios_articulos_historial (
                    precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                    lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                    lista_precio_anterior_moneda, lista_precio_nuevo_id, lista_precio_nuevo_codigo,
                    lista_precio_nuevo_nombre, lista_precio_nuevo_moneda, precio_anterior, precio_nuevo,
                    usuario_nombre, registrado_en, created_at, updated_at
                ) VALUES (
                    NEW.id, NEW.articulo_id,
                    (SELECT codigo FROM alm_articulos WHERE id = NEW.articulo_id),
                    (SELECT COALESCE(nombre_comercial, descripcion) FROM alm_articulos WHERE id = NEW.articulo_id),
                    'actualizado', OLD.lista_precio_id,
                    (SELECT codigo FROM alm_listas_precios WHERE id = OLD.lista_precio_id),
                    (SELECT nombre FROM alm_listas_precios WHERE id = OLD.lista_precio_id),
                    (SELECT moneda FROM alm_listas_precios WHERE id = OLD.lista_precio_id),
                    NEW.lista_precio_id,
                    (SELECT codigo FROM alm_listas_precios WHERE id = NEW.lista_precio_id),
                    (SELECT nombre FROM alm_listas_precios WHERE id = NEW.lista_precio_id),
                    (SELECT moneda FROM alm_listas_precios WHERE id = NEW.lista_precio_id),
                    OLD.precio, NEW.precio, 'Sistema (BD)', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                );
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_precios_articulos_audit_delete
            AFTER DELETE ON alm_precios_articulos
            WHEN NOT EXISTS (
                SELECT 1 FROM alm_precios_articulos_historial
                WHERE precio_articulo_id = OLD.id
                    AND accion = 'eliminado'
                    AND registrado_en = CURRENT_TIMESTAMP
            )
            BEGIN
                INSERT INTO alm_precios_articulos_historial (
                    precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                    lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                    lista_precio_anterior_moneda, precio_anterior, usuario_nombre, registrado_en, created_at, updated_at
                ) VALUES (
                    OLD.id, OLD.articulo_id,
                    (SELECT codigo FROM alm_articulos WHERE id = OLD.articulo_id),
                    (SELECT COALESCE(nombre_comercial, descripcion) FROM alm_articulos WHERE id = OLD.articulo_id),
                    'eliminado', OLD.lista_precio_id,
                    (SELECT codigo FROM alm_listas_precios WHERE id = OLD.lista_precio_id),
                    (SELECT nombre FROM alm_listas_precios WHERE id = OLD.lista_precio_id),
                    (SELECT moneda FROM alm_listas_precios WHERE id = OLD.lista_precio_id),
                    OLD.precio, 'Sistema (BD)', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                );
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_articulos_audit_precios_delete
            BEFORE DELETE ON alm_articulos
            BEGIN
                INSERT INTO alm_precios_articulos_historial (
                    precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                    lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                    lista_precio_anterior_moneda, precio_anterior, usuario_nombre,
                    registrado_en, created_at, updated_at
                )
                SELECT p.id, p.articulo_id, OLD.codigo, COALESCE(OLD.nombre_comercial, OLD.descripcion), 'eliminado',
                    p.lista_precio_id, l.codigo, l.nombre, l.moneda, p.precio, 'Sistema (BD)',
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                FROM alm_precios_articulos p
                LEFT JOIN alm_listas_precios l ON l.id = p.lista_precio_id
                WHERE p.articulo_id = OLD.id;
            END
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER alm_listas_precios_audit_precios_delete
            BEFORE DELETE ON alm_listas_precios
            BEGIN
                INSERT INTO alm_precios_articulos_historial (
                    precio_articulo_id, articulo_id, articulo_codigo, articulo_nombre, accion,
                    lista_precio_anterior_id, lista_precio_anterior_codigo, lista_precio_anterior_nombre,
                    lista_precio_anterior_moneda, precio_anterior, usuario_nombre,
                    registrado_en, created_at, updated_at
                )
                SELECT p.id, p.articulo_id, a.codigo, COALESCE(a.nombre_comercial, a.descripcion), 'eliminado',
                    OLD.id, OLD.codigo, OLD.nombre, OLD.moneda, p.precio, 'Sistema (BD)',
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                FROM alm_precios_articulos p
                JOIN alm_articulos a ON a.id = p.articulo_id
                WHERE p.lista_precio_id = OLD.id;
            END
            SQL);
    }

    private function dropExistingTriggers(): void
    {
        $quote = DB::getDriverName() === 'mysql'
            ? fn (string $trigger): string => "`{$trigger}`"
            : fn (string $trigger): string => $trigger;

        foreach (self::TRIGGERS as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$quote($trigger));
        }
    }
};
