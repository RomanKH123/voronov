'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../style/js/work.js'), 'utf8');

function navigation(referrer, historyLength = 2) {
    let ready, click, wentBack = false;
    const link = {
        href: '/work.html', lastChild: { textContent: ' Назад к работам' },
        addEventListener(type, handler) { if (type === 'click') click = handler; }
    };
    const document = {
        referrer,
        querySelector() { return null; },
        querySelectorAll() { return [link]; },
        addEventListener(type, handler) { if (type === 'DOMContentLoaded') ready = handler; }
    };
    vm.runInNewContext(source, {
        document, URL,
        window: {
            location: { href: 'https://voronov-art.ru/work_info.html?id=1' },
            history: { length: historyLength, back() { wentBack = true; } }
        }
    });
    ready();
    return { link, click(modifiers = {}) {
        let prevented = false;
        click({ button: 0, preventDefault() { prevented = true; }, ...modifiers });
        return { prevented, wentBack };
    } };
}

for (const referrer of ['', 'https://external.example/', 'https://voronov-art.ru/work_info.html?id=1']) {
    const nav = navigation(referrer);
    assert.equal(nav.link.href, '/work.html');
    assert.deepEqual(nav.click(), { prevented: false, wentBack: false });
}
const filtered = navigation('https://voronov-art.ru/work.html?kategory=Sites');
assert.equal(filtered.link.href, '/work.html?kategory=Sites');
assert.deepEqual(filtered.click(), { prevented: true, wentBack: true });
const home = navigation('https://voronov-art.ru/#works');
assert.equal(home.link.href, '/#works');
assert.deepEqual(home.click({ ctrlKey: true }), { prevented: false, wentBack: false });
const singleEntry = navigation('https://voronov-art.ru/#works', 1);
assert.deepEqual(singleEntry.click(), { prevented: false, wentBack: false });
console.log('PASS: same-site return, filtered portfolio, homepage anchor, direct/external visits, modified clicks and missing history.');
