const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../public/sw.js'), 'utf8');
function worker(cacheControl = '') {
    const listeners = {}; const writes = [];
    const context = { URL, self: { location: { origin: 'https://app.example' }, addEventListener: (name, callback) => { listeners[name] = callback; } },
        fetch: async () => ({ ok: true, headers: { get: () => cacheControl }, clone: () => ({}) }),
        caches: { open: async () => ({ put: (...args) => writes.push(args) }) } };
    vm.runInNewContext(source, context);
    return { listeners, writes };
}
test('OAuth and settings navigation never enters service-worker caching', () => {
    for (const path of ['/settings/automation', '/settings/automation/facebook/connect', '/settings/automation/facebook/callback?code=secret', '/settings/automation/facebook/pages']) {
        const { listeners } = worker(); let handled = false;
        listeners.fetch({ request: { method: 'GET', url: 'https://app.example' + path, destination: 'document' }, respondWith: () => { handled = true; } });
        assert.equal(handled, false);
    }
});
test('private and no-store responses cannot enter offline cache', async () => {
    for (const header of ['no-store', 'private, max-age=0']) {
        const { listeners, writes } = worker(header); let pending;
        listeners.fetch({ request: { method: 'GET', url: 'https://app.example/other', destination: 'document' }, respondWith: promise => { pending = promise; } });
        await pending; await Promise.resolve(); assert.equal(writes.length, 0);
    }
});
test('public static resources can still be cached', async () => {
    const { listeners, writes } = worker('public, max-age=3600'); let pending;
    listeners.fetch({ request: { method: 'GET', url: 'https://app.example/logo.png', destination: 'image' }, respondWith: promise => { pending = promise; } });
    await pending; await Promise.resolve(); assert.equal(writes.length, 1);
});
