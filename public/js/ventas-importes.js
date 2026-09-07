(() => {
    const redondear = (valor) => Math.round((valor + Number.EPSILON) * 1e6) / 1e6;
    const numero = (valor) => valor === null || valor === undefined || valor === '' ? 0 : Number(valor);
    const calcular = (fila) => {
        const cantidad = numero(fila.cantidad);
        const precio = numero(fila.precio_unitario);
        const porcentaje = numero(fila.descuento_porcentaje);
        const base = redondear(cantidad * precio);
        const descuento = fila._descuento_tipo === 'porcentaje'
            ? redondear(base * porcentaje / 100) : numero(fila.descuento);
        if (![cantidad, precio, porcentaje, descuento, base].every(Number.isFinite)
            || cantidad < 0 || precio < 0 || porcentaje < 0 || porcentaje > 100 || descuento < 0 || descuento > base) {
            return { ...fila, subtotal: null, impuesto: null, total: null };
        }
        const subtotal = redondear(base - descuento);
        const aplicaIva = [true, 1, '1'].includes(fila.aplicar_iva);
        const impuesto = aplicaIva ? redondear(subtotal * 0.13) : 0;
        return { ...fila, descuento, descuento_porcentaje: base > 0 ? redondear(descuento / base * 100) : 0,
            subtotal, impuesto, total: redondear(subtotal + impuesto) };
    };
    const actualizar = (wire, ruta, campo, valor) => {
        const fila = { ...(wire.$get(ruta) || {}) };
        if (campo !== undefined) fila[campo] = valor;
        if (campo === 'descuento' || campo === 'descuento_porcentaje') {
            fila._descuento_tipo = campo === 'descuento' ? 'importe' : 'porcentaje';
        }
        // false conserva los cambios en el navegador hasta el siguiente envío explícito.
        wire.$set(ruta, calcular(fila), false);
    };
    const sumar = (filas, campo, envio = 0, pagado = 0) => {
        let suma = 0;
        for (const fila of Object.values(filas || {})) {
            if (!fila || typeof fila !== 'object') continue;
            const valor = fila[campo === 'saldo' ? 'total' : campo];
            if (valor === null) return null;
            suma += numero(valor);
        }
        if (campo === 'total' || campo === 'saldo') suma += numero(envio);
        if (campo === 'saldo') suma = Math.max(0, suma - numero(pagado));
        return redondear(suma);
    };
    const formato = (valor, moneda = 'BOB') => {
        if (valor === null || valor === undefined || !Number.isFinite(Number(valor))) return '—';
        const simbolo = { BOB: 'Bs', USD: '$', EUR: '€' }[moneda] || moneda;
        return simbolo + ' ' + Number(valor).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    globalThis.siaVentasImportes = { calcular, actualizar, sumar, formato };
})();
