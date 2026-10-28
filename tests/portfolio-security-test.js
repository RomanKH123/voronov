'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const root = path.join(__dirname, '..');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const fixtures = [];
const hostile = `" autofocus onfocus="alert(999)" data-attack="'><svg/onload=alert(999)>\\');alert(999);//`;
const work = { id: 1, title: hostile, category: hostile, Kategory: hostile,
    description: hostile, full_description: hostile, image: '/img/test" autofocus onfocus="alert(999)', url: '<img onerror=x>',
    login: hostile, name: hostile, role: 'editor', created_at: '2026-10-28' };

function environment(mode, image) {
    let ready;
    let payload = { success: true, data: mode === 'detail' ? { ...work, image } : [{ ...work, image }] };
    class Element {
        constructor() { this.style = {}; this.classList = { add() {}, toggle() {} }; this.children = []; }
        set innerHTML(value) { this.html = value; if (value) fixtures.push(value); }
        get innerHTML() { return this.html || ''; }
        appendChild(child) { this.children.push(child); return child; }
        setAttribute() {}
        querySelectorAll() { return []; }
        addEventListener() {}
        remove() {}
    }
    const list = new Element();
    const document = {
        head: new Element(),
        createElement: () => new Element(),
        addEventListener: (type, callback) => { if (type === 'DOMContentLoaded') ready = callback; },
        querySelector: selector => selector === '.work-grid' && mode === 'list' ? list :
            selector === '.work-info' && mode === 'detail' ? list : null,
        getElementById: id => ['projects-list', 'works-list', 'users-list', 'works-count', 'users-count'].includes(id) ? list : null,
    };
    const context = vm.createContext({ document, URL, URLSearchParams, console,
        window: { location: { href: 'https://voronov-art.ru/work_info.html?id=1', search: '?id=1' } },
        fetch: async () => ({ json: async () => payload, ok: true }), setTimeout() {}, initWorkOrder() {}, confirm: () => true,
    });
    vm.runInContext(read('style/js/portfolio-security.js'), context);
    context.PortfolioSecurity = context.window.PortfolioSecurity;
    return { context, start: () => ready?.(), setPayload: value => { payload = value; } };
}

(async () => {
    const base = environment('list', '').context;
    const security = base.PortfolioSecurity;
    assert.equal(security.escapeHtml(`&<>"'`), '&amp;&lt;&gt;&quot;&#39;');
    assert.equal(security.escapeHtml(null), '');
    for (const value of ['javascript:alert(999)', 'data:image/svg+xml,test', 'file:///etc/passwd', 'https://u:p@example.com/a', 'https://example.com/a\n']) {
        assert.equal(security.imageUrl(value), '', value);
    }
    assert.equal(security.imageUrl('/img/test.webp'), 'https://voronov-art.ru/img/test.webp');
    // Run the actual public renderers, including old records with hostile attribute values.
    for (const mode of ['home', 'list', 'detail']) {
        for (const image of [work.image, 'javascript:alert(999)', '/img/normal.webp']) {
            const env = environment(mode, image);
            vm.runInContext(read(mode === 'home' ? 'style/js/home-works.js' : 'style/js/work.js'), env.context);
            env.start();
            await new Promise(setImmediate);
        }
    }
    const env = environment('cmm', work.image);
    const inline = read('CMM/index.php').match(/<script>\s*var API = '\/CMM\/api\.php';([\s\S]*?)<\/script>/)[0]
        .replace(/^<script>/, '').replace(/<\/script>$/, '').split('    // Старт')[0];
    vm.runInContext(inline, env.context);
    vm.runInContext('loadWorks()', env.context);
    await new Promise(setImmediate);
    vm.runInContext('loadUsers()', env.context);
    await new Promise(setImmediate);
    assert(fixtures.length >= 14, 'Renderers did not produce the expected fixtures');
    process.stdout.write(execFileSync(process.env.PHP_BINARY || 'php', [path.join(__dirname, 'portfolio-html-check.php')], {
        input: JSON.stringify(fixtures), encoding: 'utf8'
    }));
    for (const endpoint of ['CMM/api.php', 'CMM/upload.php', 'CMM/convert-all.php']) {
        const output = execFileSync(process.env.PHP_BINARY || 'php', [
            '-d', 'session.save_path=' + require('node:os').tmpdir(), '-r',
            `register_shutdown_function(function () { if (http_response_code() !== 401) exit(1); }); $_SERVER['REQUEST_METHOD'] = 'GET'; require $argv[1];`,
            path.join(root, endpoint)
        ], { encoding: 'utf8' });
        assert.equal(JSON.parse(output).success, false, endpoint + ' allowed anonymous access');
    }
    console.log('PASS: public and CMM renderers, quote encoding, unsafe URL rejection.');
    console.log('PASS: CMM API, upload and conversion endpoints reject anonymous requests with HTTP 401.');
})().catch(error => { console.error(error); process.exitCode = 1; });
