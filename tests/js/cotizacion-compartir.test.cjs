const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function componente(share) {
    const plantilla = fs.readFileSync('resources/views/filament/ventas/compartir-cotizacion.blade.php', 'utf8');
    const metodo = plantilla.match(/async compartir\(\) \{[\s\S]*?\n    \}/)[0];
    return vm.runInNewContext(`({ archivo: {}, compatible: true, ocupado: false, ${metodo} })`, {
        navigator: { share },
    });
}

test('comparte el documento sin texto, título ni enlace que sustituya el adjunto', async () => {
    let datos;
    const modal = componente(async (payload) => { datos = payload; });
    const archivo = modal.archivo;
    await modal.compartir();
    assert.deepEqual(Object.keys(datos), ['files']);
    assert.equal(datos.files.length, 1);
    assert.equal(datos.files[0], archivo);
    assert.equal(modal.ocupado, false);
    assert.match(modal.estado, /Comprueba que WhatsApp muestre el documento adjunto/);
});

test('cancelar no se presenta como envío y permite volver a compartir', async () => {
    const modal = componente(async () => { throw { name: 'AbortError' }; });
    await modal.compartir();
    assert.match(modal.estado, /Se canceló/);
    assert.equal(modal.ocupado, false);
});

test('sin soporte para archivos no intenta compartir solo texto', async () => {
    let llamadas = 0;
    const modal = componente(async () => { llamadas++; });
    modal.compatible = false;
    await modal.compartir();
    assert.equal(llamadas, 0);
});
