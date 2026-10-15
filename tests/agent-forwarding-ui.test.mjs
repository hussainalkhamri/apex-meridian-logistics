import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const template = readFileSync(new URL('../resources/views/partials/booking-detail-modal.blade.php', import.meta.url), 'utf8');
const source = [...template.matchAll(/<script>([\s\S]*?)<\/script>/g)].at(-1)[1]
    .replace(/const strings = [^\r\n]+/, 'const strings = { agent_whatsapp_opened: "Opened :count", agent_whatsapp_partial: "Opened :count; failed :failed", agent_email_prepared: "Prepared :count", agent_email_partial: "Prepared :count; failed :failed" };');
const settle = () => new Promise(resolve => setImmediate(resolve));

class Element {
    constructor(tag = 'div') {
        this.tagName = tag.toUpperCase();
        this.children = [];
        this.listeners = {};
        this.style = {};
        this.value = '';
        this.classList = { add() {}, remove() {}, toggle() {} };
    }
    append(...items) { items.forEach(item => { this.children.push(item); item.parentNode = this; }); }
    appendChild(item) { this.append(item); }
    replaceChildren(...items) { this.children = []; this.append(...items); }
    querySelectorAll(selector) { return this.children.flatMap(child => [...(selector === 'input' && child.tagName === 'INPUT' ? [child] : []), ...child.querySelectorAll(selector)]); }
    setAttribute(key, value) { this[key] = value; }
    getAttribute(key) { return this[key]; }
    addEventListener(event, listener) { (this.listeners[event] ||= []).push(listener); }
    fire(event) { (this.listeners[event] || []).forEach(listener => listener({ target: this })); }
}

function fixture({ blocked = [], missing = [], fail = false, invalidJson = false } = {}) {
    const elements = new Map();
    const el = id => {
        if (!elements.has(id)) elements.set(id, new Element());
        return elements.get(id);
    };
    const body = new Element('body');
    el('agent-forward-modal').parentNode = body;
    const agents = [1, 2, 3].map(id => ({ id, name: `Agent ${id}`, phone: `96770000000${id}`, email: `agent${id}@example.com` }));
    const popups = [];
    const requests = [];
    const toasts = [];
    let finish;
    const window = {
        location: { pathname: '/pricing' },
        open(url) {
            assert.equal(url, 'about:blank');
            const popup = blocked.includes(popups.length) ? null : {
                location: { href: url }, closed: false, close() { this.closed = true; },
            };
            popups.push(popup);
            return popup;
        },
    };
    const context = vm.createContext({
        window, console, AbortController, setTimeout, clearTimeout,
        document: {
            body, documentElement: { lang: 'en' }, createElement: tag => new Element(tag),
            getElementById: el, querySelector: () => ({ getAttribute: () => 'csrf-test' }),
        },
        showToast: (...args) => toasts.push(args), formatBookingCargoLabel: () => 'FCL',
        fetch(url, options = {}) {
            if (url.startsWith('/ajax/agents')) return Promise.resolve({ ok: true, json: async () => agents });
            if (url.startsWith('/orders/')) return Promise.resolve({ json: async () => ({ id: 'BK-UI', route: { pol: 'Shanghai', pod: 'Aden' }, cargo: {} }) });
            const payload = JSON.parse(options.body);
            requests.push({ payload, options, popupsAtRequest: popups.length });
            return new Promise(resolve => {
                finish = () => {
                    const valid = payload.agent_ids.filter(id => !missing.includes(id));
                    const response = {
                        status: fail ? 'error' : 'ok', message: 'Request failed',
                        whatsapp_urls: valid.map(id => `https://wa.me/96770000000${id}?text=message`),
                        sent_count: valid.length,
                        failed_agents: missing.map(id => ({ id, reason: 'missing_phone' })),
                        mailto_url: 'mailto:?bcc=agent1%40example.com,agent2%40example.com&subject=Quote&body=Message',
                    };
                    resolve({ ok: !fail, text: async () => invalidJson ? '<html>Error</html>' : JSON.stringify(response) });
                };
            });
        },
    });
    vm.runInContext(source, context);
    return {
        el, window, popups, requests, toasts,
        async open() { window.openAgentForwardModal('BK-UI'); await settle(); },
        select(id, checked = true) {
            const checkbox = el('agents-list').querySelectorAll('input').find(input => Number(input.value) === id);
            assert.equal(checkbox.type, 'checkbox');
            checkbox.checked = checked;
            checkbox.fire('change');
        },
        async finish() { finish(); await settle(); },
    };
}

test('multiple choices toggle both sets of send buttons and switching agent type clears them immediately', async () => {
    const ui = fixture();
    await ui.open();
    assert.equal(ui.el('agent-forward-whatsapp').disabled, true);
    ui.select(1);
    ui.select(2);
    assert.equal(ui.el('agent-forward-email').disabled, false);
    ui.select(1, false);
    assert.equal(ui.el('toolbar-agent-whatsapp').disabled, false);
    ui.select(2, false);
    assert.equal(ui.el('toolbar-agent-email').disabled, true);
    ui.select(3);
    ui.el('agent-type-external').fire('click');
    assert.equal(ui.el('agent-forward-whatsapp').disabled, true);
    assert.equal(ui.el('toolbar-agent-email').disabled, true);
    await settle();
    assert.equal(ui.el('agent-forward-email').disabled, true);
});

test('all WhatsApp windows open synchronously, selected order is preserved, and duplicate clicks are ignored', async () => {
    const ui = fixture();
    await ui.open();
    ui.select(3);
    ui.select(1);
    ui.el('agent-forward-whatsapp').fire('click');
    assert.equal(ui.requests[0].popupsAtRequest, 2);
    assert.deepEqual(ui.requests[0].payload.agent_ids, [3, 1]);
    assert.equal(ui.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-test');
    ui.el('toolbar-agent-whatsapp').fire('click');
    assert.equal(ui.requests.length, 1);
    await ui.finish();
    assert.match(ui.popups[0].location.href, /967700000003/);
    assert.match(ui.popups[1].location.href, /967700000001/);
    assert.equal(ui.toasts.at(-1)[0], 'Opened 2');
});

test('missing contacts close their blank window and blocked popups get accurate counts and manual links', async () => {
    const ui = fixture({ blocked: [2], missing: [2] });
    await ui.open();
    [1, 2, 3].forEach(id => ui.select(id));
    ui.el('agent-forward-whatsapp').fire('click');
    await ui.finish();
    assert.equal(ui.popups[1].closed, true);
    assert.match(ui.popups[0].location.href, /967700000001/);
    assert.equal(ui.toasts.at(-1)[0], 'Opened 1; failed 2');
    const link = ui.el('agent-forward-results').children.find(child => child.tagName === 'A');
    assert.match(link.href, /967700000003/);
    assert.equal(link.textContent, 'Agent 3');
});

for (const failure of [{ fail: true }, { invalidJson: true }]) {
    test(`failed response closes every reserved window (${JSON.stringify(failure)})`, async () => {
        const ui = fixture(failure);
        await ui.open();
        ui.select(1);
        ui.select(2);
        ui.el('agent-forward-whatsapp').fire('click');
        await ui.finish();
        assert.ok(ui.popups.every(popup => popup.closed));
        assert.equal(ui.toasts.at(-1)[1], 'error');
        assert.equal(ui.el('agent-forward-whatsapp').disabled, false);
    });
}

test('email opens one draft from the server without WhatsApp windows or a sent claim', async () => {
    const ui = fixture();
    await ui.open();
    ui.select(1);
    ui.select(2);
    ui.el('agent-forward-email').fire('click');
    assert.equal(ui.popups.length, 0);
    await ui.finish();
    assert.match(ui.window.location.href, /^mailto:\?bcc=/);
    assert.equal(ui.toasts.at(-1)[0], 'Prepared 2');
});
