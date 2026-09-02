<?php

namespace App\Services\Sistema;

use DomainException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class NotificacionExcepcionOperativaService
{
    public function esOperativa(Throwable $exception): bool
    {
        return $exception instanceof RuntimeException
            || $exception instanceof DomainException
            || $exception instanceof InvalidArgumentException;
    }

    public function contenido(Throwable $exception): array
    {
        $mensaje = trim($exception->getMessage()) ?: 'La operación no pudo completarse con los datos actuales.';
        $texto = mb_strtolower($mensaje);

        [$titulo, $solucion] = match (true) {
            str_contains($texto, 'almacén activo') => [
                'No hay un almacén disponible',
                'Cree o active un almacén para la empresa y sucursal seleccionadas, o corrija la empresa/sucursal del documento. Luego vuelva a intentar.',
            ],
            str_contains($texto, 'stock insuficiente'), str_contains($texto, 'no hay stock disponible'), str_contains($texto, 'no existe stock') => [
                'Inventario insuficiente',
                'Revise las existencias y las cantidades comprometidas del artículo en el almacén. Registre una entrada, libere una reserva o reduzca la cantidad solicitada.',
            ],
            str_contains($texto, 'capas de costo') => [
                'No se pudo valorar la salida',
                'Revise las capas FIFO/LIFO del artículo y regularice las entradas con costo antes de registrar la salida.',
            ],
            str_contains($texto, 'costo estándar') => [
                'Falta el costo estándar',
                'Configure un costo estándar mayor a cero en el artículo y vuelva a ejecutar la operación.',
            ],
            str_contains($texto, 'serie'), str_contains($texto, 'lote') => [
                'Datos de trazabilidad incompletos',
                'Revise las series o lotes seleccionados. La cantidad informada debe coincidir con las unidades del movimiento y estar disponible en el almacén.',
            ],
            str_contains($texto, 'pago total'), str_contains($texto, 'saldo pendiente') => [
                'La factura todavía tiene saldo',
                'Registre y confirme el importe pendiente en Pagos. La entrega y la salida de inventario se procesarán al completar el total.',
            ],
            str_contains($texto, 'período contable'), str_contains($texto, 'periodo contable') => [
                'El período contable no está disponible',
                'Abra el período correspondiente o seleccione una fecha contable perteneciente a un período abierto.',
            ],
            str_contains($texto, 'balanceado') => [
                'El asiento contable no está balanceado',
                'Revise las cuentas y los importes del Debe y Haber antes de confirmar nuevamente.',
            ],
            str_contains($texto, 'ya fue procesado'), str_contains($texto, 'ya está pagada'), str_contains($texto, 'ya está pagado') => [
                'La operación ya fue realizada',
                'Actualice la página y revise el estado y los documentos relacionados antes de intentar otra acción.',
            ],
            default => [
                'No se pudo completar la operación',
                'Revise la configuración y los datos indicados, corrija la causa y vuelva a intentar. Si el mensaje persiste, comuníquelo al administrador.',
            ],
        };

        return compact('titulo', 'mensaje', 'solucion');
    }
}
