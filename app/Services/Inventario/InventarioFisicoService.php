<?php

namespace App\Services\Inventario;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\InventarioConteo;
use App\Models\Inventario\InventarioFisico;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventarioFisicoService
{
    public const PROGRAMAR = 'programar_inventario_almacen::inventario';

    public const CONTAR = 'update_almacen::inventario';

    public const VER = 'view_any_almacen::inventario';

    private function autorizar(string $permiso, ?InventarioFisico $inventario = null): User
    {
        $user = Auth::user();
        abort_unless($user && $user->can(self::VER) && $user->can($permiso), 403);
        if ($inventario) {
            abort_unless(InventarioFisico::delUsuario($user)->whereKey($inventario->id)->exists(), 403);
        }

        return $user;
    }

    private function exigir(bool $condicion, string $mensaje): void
    {
        if (! $condicion) {
            throw ValidationException::withMessages(['inventario' => $mensaje]);
        }
    }

    private function evento(InventarioFisico $inventario, string $accion, array $antes = [], array $despues = [], ?string $motivo = null, ?int $conteoId = null): void
    {
        $inventario->eventos()->create([
            'usuario_id' => Auth::id(), 'accion' => $accion, 'antes' => $antes,
            'despues' => $despues, 'motivo' => $motivo, 'conteo_id' => $conteoId,
            'created_at' => now(),
        ]);
    }

    public function programar(array $data): InventarioFisico
    {
        $user = $this->autorizar(self::PROGRAMAR);
        $data = Validator::make($data, [
            'empresa_id' => 'required|integer', 'sucursal_id' => 'required|integer',
            'almacen_id' => 'required|integer', 'responsable_id' => 'required|integer|exists:users,id',
            'fecha_programada' => 'required|date|after_or_equal:today', 'observaciones' => 'nullable|string|max:4000',
        ])->validate();

        return DB::transaction(function () use ($data, $user) {
            $almacen = Almacen::whereKey($data['almacen_id'])->lockForUpdate()->firstOrFail();
            $this->exigir($almacen->activo && (int) $almacen->empresa_id === (int) $data['empresa_id']
                && (int) $almacen->sucursal_id === (int) $data['sucursal_id'], 'El almacén debe estar activo y pertenecer a la empresa y sucursal seleccionadas.');
            abort_if(($user->empresa_id && (int) $user->empresa_id !== (int) $almacen->empresa_id)
                || ($user->sucursal_id && (int) $user->sucursal_id !== (int) $almacen->sucursal_id), 403);
            $responsable = User::findOrFail($data['responsable_id']);
            $this->exigir($responsable->can(self::CONTAR) && $responsable->can(self::VER)
                && (! $responsable->empresa_id || (int) $responsable->empresa_id === (int) $almacen->empresa_id)
                && (! $responsable->sucursal_id || (int) $responsable->sucursal_id === (int) $almacen->sucursal_id), 'El responsable debe tener acceso al conteo en esta empresa y sucursal.');
            $this->exigir(! InventarioFisico::where('almacen_id', $almacen->id)
                ->whereIn('estado', ['programado', 'en_conteo', 'en_revision'])->exists(), 'Este almacén ya tiene un inventario abierto. Ciérrelo o cancélelo antes de programar otro.');
            $inventario = InventarioFisico::create($data + [
                'codigo' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'estado' => 'programado', 'creado_por' => $user->id,
            ]);
            $this->evento($inventario, 'programado', [], $inventario->only(['codigo', 'empresa_id', 'sucursal_id', 'almacen_id', 'fecha_programada', 'responsable_id', 'observaciones']));

            return $inventario;
        });
    }

    public function iniciar(InventarioFisico $sesion): void
    {
        $this->autorizar(self::PROGRAMAR, $sesion);
        DB::transaction(function () use ($sesion) {
            $inv = InventarioFisico::lockForUpdate()->findOrFail($sesion->id);
            $this->exigir($inv->estado === 'programado', 'Solo se puede iniciar un inventario programado.');
            $this->exigir(! $inv->fecha_programada->isFuture(), 'El inventario no puede iniciarse antes de la fecha programada.');
            $this->exigir($inv->almacen->activo, 'El almacén está inactivo.');
            // Incluye el catálogo inventariable activo y los artículos con existencias, incluso inactivos.
            $articulos = Articulo::where('empresa_id', $inv->empresa_id)
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('activo', true)->where('inventariable', true))
                    ->orWhereHas('existencias', fn ($q) => $q->where('almacen_id', $inv->almacen_id)))
                ->with(['unidadMedida', 'existencias' => fn ($q) => $q->where('almacen_id', $inv->almacen_id)])
                ->get();
            $this->exigir($articulos->isNotEmpty(), 'No hay artículos inventariables para esta empresa.');
            foreach ($articulos as $articulo) {
                $stock = $articulo->existencias->first();
                $inv->conteos()->create([
                    'articulo_id' => $articulo->id, 'codigo' => $articulo->codigo,
                    'nombre' => $articulo->nombre_comercial ?: $articulo->descripcion ?: $articulo->codigo,
                    'unidad' => $articulo->unidadMedida?->abreviatura,
                    'stock_sistema' => $stock?->cantidad_disponible ?? 0,
                    'stock_reservado' => $stock?->cantidad_comprometida ?? 0,
                ]);
            }
            $inv->update(['estado' => 'en_conteo', 'iniciado_at' => now()]);
            $this->evento($inv, 'iniciado', ['estado' => 'programado'], ['estado' => 'en_conteo', 'articulos' => $articulos->count()]);
        });
    }

    public function buscarCodigo(InventarioFisico $inv, string $codigo): InventarioConteo
    {
        $this->autorizar(self::CONTAR, $inv);
        $codigo = trim($codigo);
        $this->exigir($codigo !== '', 'Escanee o ingrese un código.');
        $resultados = $inv->conteos()->where(function ($q) use ($codigo, $inv) {
            $q->where('codigo', $codigo)->orWhereHas('articulo', function ($q) use ($codigo, $inv) {
                $q->where('codigo', $codigo)->orWhere('codigo_alterno', $codigo)
                    ->orWhereHas('codigosBarras', fn ($q) => $q->where('codigo_barras', $codigo))
                    ->orWhereHas('series', fn ($q) => $q->where('numero_serie', $codigo)->where('almacen_id', $inv->almacen_id));
            });
        })->limit(2)->get();
        $this->exigir($resultados->count() === 1, $resultados->isEmpty()
            ? 'El código no corresponde a un artículo de este inventario. Busque por nombre o revise su registro en Artículos.'
            : 'El código corresponde a varios artículos. Seleccione el artículo desde la lista.');

        return $resultados->first();
    }

    public function contar(InventarioConteo $linea, array $data): void
    {
        $this->autorizar(self::CONTAR, $linea->inventarioFisico);
        $data = Validator::make($data, [
            'cantidad_contada' => ['required', 'numeric', 'min:0', 'max:999999999999.999999', 'decimal:0,6'],
            'version' => 'required|integer|min:0', 'observaciones' => 'nullable|string|max:4000',
            'codigo_leido' => 'nullable|string|max:1000', 'motivo' => 'nullable|string|max:4000',
        ])->validate();
        DB::transaction(function () use ($linea, $data) {
            $inv = InventarioFisico::lockForUpdate()->findOrFail($linea->inventario_fisico_id);
            $this->exigir($inv->estado === 'en_conteo', 'Solo se pueden registrar conteos mientras el inventario está en conteo.');
            $conteo = $inv->conteos()->lockForUpdate()->findOrFail($linea->id);
            $this->exigir((int) $conteo->version === (int) $data['version'], 'Otro usuario modificó este conteo. Cierre el formulario y vuelva a abrirlo.');
            $this->exigir($conteo->cantidad_contada === null || filled($data['motivo'] ?? null), 'Indique el motivo del reconteo.');
            $this->exigir(round((float) $data['cantidad_contada'] - (float) $conteo->stock_sistema, 6) === 0.0
                || filled($data['observaciones'] ?? null), 'Describa la diferencia encontrada con el stock de referencia.');
            if (filled($data['codigo_leido'] ?? null)) {
                $this->exigir($this->buscarCodigo($inv, $data['codigo_leido'])->id === $conteo->id, 'El código escaneado pertenece a otro artículo.');
            }
            $antes = $conteo->only(['cantidad_contada', 'observaciones', 'codigo_leido', 'version']);
            $conteo->update([
                'cantidad_contada' => $data['cantidad_contada'], 'observaciones' => $data['observaciones'] ?? null,
                'codigo_leido' => $data['codigo_leido'] ?? null, 'version' => $conteo->version + 1,
                'contado_por' => Auth::id(), 'contado_at' => now(),
                'revisado_por' => null, 'revisado_at' => null, 'nota_revision' => null,
            ]);
            $this->evento($inv, $antes['cantidad_contada'] === null ? 'conteo' : 'reconteo', $antes,
                $conteo->only(['cantidad_contada', 'observaciones', 'codigo_leido', 'version']), $data['motivo'] ?? null, $conteo->id);
        });
    }

    public function enviarRevision(InventarioFisico $sesion): void
    {
        $this->autorizar(self::CONTAR, $sesion);
        DB::transaction(function () use ($sesion) {
            $inv = InventarioFisico::lockForUpdate()->findOrFail($sesion->id);
            $this->exigir($inv->estado === 'en_conteo', 'El inventario debe estar en conteo.');
            $this->exigir($inv->conteos()->exists() && ! $inv->conteos()->whereNull('cantidad_contada')->exists(), 'Complete el 100% de los artículos antes de enviar a revisión; cero es un conteo válido.');
            $inv->update(['estado' => 'en_revision']);
            $this->evento($inv, 'enviado_revision', ['estado' => 'en_conteo'], ['estado' => 'en_revision']);
        });
    }

    public function revisar(InventarioConteo $linea, string $nota): void
    {
        $this->autorizar(self::PROGRAMAR, $linea->inventarioFisico);
        $this->exigir(filled($nota) && mb_strlen($nota) <= 4000, 'Registre una conclusión de auditoría de hasta 4000 caracteres.');
        DB::transaction(function () use ($linea, $nota) {
            $inv = InventarioFisico::lockForUpdate()->findOrFail($linea->inventario_fisico_id);
            $this->exigir($inv->estado === 'en_revision', 'El inventario debe estar en revisión.');
            $conteo = $inv->conteos()->lockForUpdate()->findOrFail($linea->id);
            $this->exigir($conteo->cantidad_contada !== null && $conteo->revisado_at === null, 'El conteo debe estar completo y pendiente de revisión.');
            $conteo->update(['revisado_por' => Auth::id(), 'revisado_at' => now(), 'nota_revision' => $nota]);
            $this->evento($inv, 'revisado', [], [
                'cantidad_contada' => $conteo->cantidad_contada, 'diferencia' => $conteo->diferencia,
                'stock_sistema_al_revisar' => Existencia::where('articulo_id', $conteo->articulo_id)->where('almacen_id', $inv->almacen_id)->sum('cantidad_disponible'),
            ], $nota, $conteo->id);
        });
    }

    public function finalizar(InventarioFisico $sesion, string $accion, string $motivo): void
    {
        $this->autorizar(self::PROGRAMAR, $sesion);
        $this->exigir(filled($motivo) && mb_strlen($motivo) <= 4000, 'Indique un motivo o conclusión de hasta 4000 caracteres.');
        DB::transaction(function () use ($sesion, $accion, $motivo) {
            $inv = InventarioFisico::lockForUpdate()->findOrFail($sesion->id);
            $antes = $inv->estado;
            if ($accion === 'cerrar') {
                $this->exigir($antes === 'en_revision', 'El inventario debe estar en revisión.');
                $this->exigir(! $inv->conteos()->whereNull('revisado_at')->exists(), 'Todos los artículos deben estar revisados antes del cierre.');
                $estado = 'cerrado';
            } elseif ($accion === 'devolver') {
                $this->exigir($antes === 'en_revision', 'Solo se puede devolver a conteo un inventario en revisión.');
                $estado = 'en_conteo';
                $inv->conteos()->update(['revisado_por' => null, 'revisado_at' => null, 'nota_revision' => null]);
            } elseif ($accion === 'cancelar') {
                $this->exigir(in_array($antes, ['programado', 'en_conteo', 'en_revision'], true), 'No se puede cancelar un inventario cerrado o cancelado.');
                $estado = 'cancelado';
            } else {
                $this->exigir(false, 'Acción no permitida.');
            }
            $inv->update(['estado' => $estado, 'cerrado_at' => in_array($estado, ['cerrado', 'cancelado']) ? now() : null]);
            $this->evento($inv, $accion, ['estado' => $antes], ['estado' => $estado], $motivo);
        });
    }
}
