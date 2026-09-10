const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup({ reject = false, delayed = false } = {}) {
    let callback, resolve, stops = 0;
    const changes = [];
    const controls = { stop: () => stops++ };
    const window = {};
    class Reader {
        async decodeFromConstraints(constraints, video, handler) {
            callback = handler;
            if (reject) throw new Error('Camera denied');
            if (delayed) return new Promise(done => { resolve = done; });
            return controls;
        }
    }
    const source = fs.readFileSync('resources/js/barcode-scanner.js', 'utf8').replace(/^import .*;\s*/m, '');
    vm.runInNewContext(source, { window, BrowserMultiFormatReader: Reader, BrowserCodeReader: { listVideoInputDevices: async () => [] } });
    const state = window.siaBarcodeScanner({ $set: (...args) => changes.push(args) }, 'mountedActions.0.data.codigo');
    state.$nextTick = async () => {};
    state.$refs = { video: {} };
    return { state, changes, scan: code => callback({ getText: () => code }), stops: () => stops, resolve: () => resolve(controls) };
}

test('el código se envía al campo correcto una sola vez y se cierra la cámara', async () => {
    const s = setup();
    await s.state.open();
    s.scan('QR-123');
    s.scan('QR-123');
    assert.deepEqual(s.changes, [['mountedActions.0.data.codigo', 'QR-123']]);
    assert.equal(s.state.opened, false);
    assert.equal(s.stops(), 1);
});

test('dos campos no comparten lecturas ni el estado de la cámara', async () => {
    const a = setup(), b = setup();
    await a.state.open();
    await b.state.open();
    a.scan('ART-A');
    assert.equal(b.state.opened, true);
    assert.equal(b.changes.length, 0);
    b.state.destroy();
    assert.equal(b.stops(), 1);
});

test('si se cierra antes de obtener permiso, el stream tardío se detiene', async () => {
    const s = setup({ delayed: true });
    const opening = s.state.open();
    await new Promise(setImmediate);
    s.state.close();
    s.resolve();
    await opening;
    s.scan('IGNORAR');
    assert.equal(s.stops(), 1);
    assert.equal(s.changes.length, 0);
});

test('el rechazo de cámara informa y permite ingresar el código manualmente', async () => {
    const s = setup({ reject: true });
    await s.state.open();
    assert.match(s.state.error, /teclado o lector USB/);
    assert.equal(s.state.controls, null);
    assert.equal(s.changes.length, 0);
});
