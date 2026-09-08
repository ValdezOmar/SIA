const { test } = require('node:test');
const assert = require('node:assert/strict');
require('../../public/js/ventas-importes.js');
const { calcular, actualizar, sumar, formato } = globalThis.siaVentasImportes;

test('cantidad, descuento, IVA y total se calculan sin duplicar descuentos', () => {
    const fila = calcular({ cantidad: '3', precio_unitario: '100', descuento_porcentaje: '10', _descuento_tipo: 'porcentaje', aplicar_iva: true });
    assert.equal(fila.descuento, 30);
    assert.equal(fila.subtotal, 270);
    assert.equal(fila.impuesto, 35.1);
    assert.equal(fila.total, 305.1);
    assert.equal(formato(fila.total, 'BOB'), 'Bs 305.10');
});

test('cada pulsación actualiza localmente y no solicita envío al servidor', () => {
    let fila = { cantidad: 1, precio_unitario: 100, descuento: 0 };
    const wire = { $get: () => fila, $set: (ruta, valor, enviar) => {
        assert.equal(ruta, 'data.detalles.fila');
        assert.equal(enviar, false);
        fila = valor;
    } };
    actualizar(wire, 'data.detalles.fila', 'cantidad', '3');
    assert.equal(fila.total, 300);
    actualizar(wire, 'data.detalles.fila', 'descuento_porcentaje', '10');
    assert.equal(fila.total, 270);
    actualizar(wire, 'data.detalles.fila', 'cantidad', '4');
    assert.equal(fila.total, 360);
    actualizar(wire, 'data.detalles.fila', 'descuento', '15');
    assert.equal(fila.total, 385);
    actualizar(wire, 'data.detalles.fila', 'aplicar_iva', true);
    assert.equal(fila.total, 435.05);
    actualizar(wire, 'data.detalles.fila', 'aplicar_iva', false);
    assert.equal(fila.total, 385);
});

test('resumen suma todas las filas, envío una vez y resta lo pagado', () => {
    const filas = { a: calcular({ cantidad: 3, precio_unitario: 100, descuento: 30 }), b: calcular({ cantidad: 1, precio_unitario: 50 }) };
    assert.equal(sumar(filas, 'subtotal'), 320);
    assert.equal(sumar(filas, 'descuento'), 30);
    assert.equal(sumar(filas, 'total', 25), 345);
    assert.equal(sumar(filas, 'saldo', 25, 100), 245);
    delete filas.a;
    assert.equal(sumar(filas, 'total', 25), 75);
});

test('bonificación completa e importes inválidos no muestran totales anteriores', () => {
    assert.equal(calcular({ cantidad: 1, precio_unitario: 100, descuento_porcentaje: 100, _descuento_tipo: 'porcentaje', aplicar_iva: true }).total, 0);
    const invalida = calcular({ cantidad: 1, precio_unitario: 10, descuento: 20, total: 100 });
    assert.equal(invalida.total, null);
    assert.equal(sumar({ a: invalida }, 'total'), null);
    assert.equal(formato(null), '—');
});
