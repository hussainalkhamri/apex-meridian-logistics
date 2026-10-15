import test from 'node:test';
import assert from 'node:assert/strict';
import { readdir } from 'node:fs/promises';

test('production maritime worker can calculate the Mersin to Hodeidah route', async () => {
    const directory = new URL('../public/build/assets/', import.meta.url);
    const file = (await readdir(directory)).find(name => /^sea-route\.worker-.*\.js$/.test(name));
    assert.ok(file, 'Run npm run build before this smoke test');
    let response;
    globalThis.self = { postMessage: value => { response = value; } };
    try {
        await import(new URL(file, directory));
        self.onmessage({ data: { origin: [34.633333, 36.716667], destination: [42.9, 14.833333] } });
        assert.equal(response?.geometry?.type, 'LineString');
        assert.ok(response.geometry.coordinates.length > 4);
    } finally {
        delete globalThis.self;
    }
});
