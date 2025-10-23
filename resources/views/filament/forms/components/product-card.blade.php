@php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

// Ejecutar el closure si $record es Closure
if ($record instanceof Closure) {
    $record = $record();
}

$imageUrl = $record && $record->foto_catalogo
    ? Storage::disk('public')->url($record->foto_catalogo)
    : asset('images/default-product.jpg');

$articulo = null;
if ($record && isset($record->codigo_articulo)) {
    $articulo = DB::table('alm_articulos')
        ->where('codigo', $record->codigo_articulo)
        ->select('descripcion', 'presentacion', 'proveedor', 'codigo_alterno')
        ->first();
}

$titulo = $articulo->descripcion ?? $record->descripcion ?? 'Producto sin descripción';
$presentacion = $articulo->presentacion ?? 'No especificada';
$proveedor = $articulo->proveedor ?? 'No especificado';
$codigoAlterno = $articulo->codigo_alterno ?? 'No disponible';
@endphp

@if($record && is_object($record))
<div class="bg-white dark:bg-gray-900 rounded-xl shadow-md dark:shadow-gray-800 overflow-hidden border border-gray-200 dark:border-gray-700 transition-all hover:shadow-xl">
    <div class="flex flex-col md:flex-row items-center md:items-start">

        <!-- Imagen -->
        <div class="w-full md:w-1/12 p-2 flex justify-center items-center flex-shrink-0">
            <div class="group w-full max-w-[300px] aspect-square overflow-hidden rounded-xl shadow-md dark:shadow-gray-700">
                <img 
                    src="{{ $imageUrl }}" 
                    alt="{{ $titulo }}" 
                    class="w-full h-full object-cover transition-transform duration-500 ease-in-out group-hover:scale-110"
                >
            </div>
        </div>

        <!-- Datos -->
        <div class="w-full md:w-2/3 p-4 flex flex-col justify-center">
            <!-- Título -->
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                {{ $titulo }} — {{ $record->codigo_articulo ?? 'SIN CÓDIGO' }}
            </h1>

            <!-- Descripción -->
            <h3 class="text-gray-900 dark:text-gray-100 text-justify leading-relaxed tracking-wide text-base md:text-lg mb-4">
                <div class="prose dark:prose-invert max-w-none text-justify">
                    {!! $record->descripcion ?? 'No hay descripción disponible.' !!}
                </div>
            </h3>

            <!-- Datos adicionales -->
            <div class="text-gray-800 dark:text-gray-200 text-sm space-y-1">
                <p><strong>Presentación:</strong> {{ $presentacion }}</p>
                <p><strong>Proveedor:</strong> {{ $proveedor }}</p>
                <p><strong>Código alterno:</strong> {{ $codigoAlterno }}</p>
            </div>
        </div>
    </div>
</div>
@else
<div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-red-700 dark:text-red-300 text-center">
    <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    No se pudo cargar la información del producto
</div>
@endif
