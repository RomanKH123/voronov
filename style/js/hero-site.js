/*
 * Hero городских страниц: каркасная «картинка сайта», которая при наведении
 * расслаивается на элементы — эффект взорванного макета по слоям (exploded view).
 * Без наведения — собранный макет. Чистый canvas 2D, без библиотек.
 */
(function () {
    'use strict';

    var canvas = document.getElementById('heroSite');
    if (!canvas) return;

    var host = canvas.parentElement;
    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var ACCENT = '#4a6fa5';
    var MID    = '#6f9bd1';

    // Виртуальная сетка макета: 100 x 72 единиц
    var DW = 100, DH = 72;

    // Каждый кусок: координаты в макете + layer (глубина слоя: 0 — дальний фон).
    var LAYOUT = [
        { x: 6,  y: 4,  w: 88, h: 64, r: 6, kind: 'frame', stroke: ACCENT, layer: 0 },
        // шапка браузера
        { x: 11, y: 8.5, w: 2.2, h: 2.2, kind: 'dot', fill: '#e3705f', layer: 1 },
        { x: 15, y: 8.5, w: 2.2, h: 2.2, kind: 'dot', fill: '#e6b94d', layer: 1 },
        { x: 19, y: 8.5, w: 2.2, h: 2.2, kind: 'dot', fill: '#5fb37a', layer: 1 },
        { x: 27, y: 7.5, w: 50, h: 4,  r: 2, kind: 'rect', fill: 'rgba(111,155,209,0.18)', stroke: MID, layer: 1 },
        // логотип + меню
        { x: 12, y: 18, w: 16, h: 6,  r: 2, kind: 'rect', fill: 'rgba(74,111,165,0.9)', layer: 2 },
        { x: 60, y: 19.5, w: 8, h: 3, r: 1.5, kind: 'rect', fill: 'rgba(74,111,165,0.35)', layer: 2 },
        { x: 71, y: 19.5, w: 8, h: 3, r: 1.5, kind: 'rect', fill: 'rgba(74,111,165,0.35)', layer: 2 },
        { x: 82, y: 19.5, w: 6, h: 3, r: 1.5, kind: 'rect', fill: 'rgba(74,111,165,0.35)', layer: 2 },
        // hero: текстовые строки
        { x: 12, y: 30, w: 30, h: 5, r: 2, kind: 'rect', fill: 'rgba(58,90,138,0.75)', layer: 3 },
        { x: 12, y: 38, w: 24, h: 4, r: 2, kind: 'rect', fill: 'rgba(111,155,209,0.55)', layer: 3 },
        { x: 12, y: 45, w: 17, h: 4, r: 2, kind: 'rect', fill: 'rgba(111,155,209,0.55)', layer: 3 },
        // картинка в hero
        { x: 52, y: 29, w: 36, h: 27, r: 3, kind: 'rect', fill: 'rgba(196,220,250,0.6)', stroke: MID, layer: 3 },
        { x: 57, y: 34, w: 12, h: 12, r: 2, kind: 'rect', fill: 'rgba(74,111,165,0.5)', layer: 4 },
        { x: 72, y: 44, w: 11, h: 8, r: 1.5, kind: 'rect', fill: 'rgba(111,155,209,0.55)', layer: 4 },
        // кнопка
        { x: 12, y: 53, w: 17, h: 6, r: 3, kind: 'rect', fill: ACCENT, layer: 4 },
        // карточки внизу
        { x: 12, y: 60, w: 22, h: 5, r: 2, kind: 'rect', fill: 'rgba(196,220,250,0.7)', stroke: MID, layer: 5 },
        { x: 39, y: 60, w: 22, h: 5, r: 2, kind: 'rect', fill: 'rgba(196,220,250,0.7)', stroke: MID, layer: 5 },
        { x: 66, y: 60, w: 22, h: 5, r: 2, kind: 'rect', fill: 'rgba(196,220,250,0.7)', stroke: MID, layer: 5 }
    ];

    // Направление «разлёта» слоёв (вправо-вниз — к зрителю)
    var DIR_X = 0.62, DIR_Y = 0.46, STEP = 5.2;

    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var W = 0, H = 0, scale = 1, offX = 0, offY = 0;
    var pieces = [];
    var mouse = { x: 0, y: 0 };
    var hovering = false;
    var e = 0;            // текущая степень расслоения 0..1
    var running = true;

    function rand(min, max) { return min + Math.random() * (max - min); }

    function buildPieces() {
        var cx = DW / 2, cy = DH / 2;
        pieces = LAYOUT.map(function (p) {
            var pcx = p.x + (p.w || 0) / 2;
            var pcy = p.y + (p.h || 0) / 2;
            var dirX = pcx - cx, dirY = pcy - cy;
            var len = Math.sqrt(dirX * dirX + dirY * dirY) || 1;
            return {
                def: p,
                fanX: dirX / len,           // лёгкий веер от центра
                fanY: dirY / len,
                rot: rand(-0.12, 0.12)      // едва заметный разворот
            };
        });
    }

    function resize() {
        var rect = host.getBoundingClientRect();
        W = Math.max(1, rect.width);
        H = Math.max(1, rect.height);
        canvas.width = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        var pad = 0.86;
        scale = Math.min(W / DW, H / DH) * pad;
        offX = (W - DW * scale) / 2;
        offY = (H - DH * scale) / 2;
    }

    function roundRect(x, y, w, h, r) {
        r = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    function drawPiece(p, parX, parY) {
        var d = p.def;
        var layer = d.layer;

        // расслоение: слой уезжает по диагонали + лёгкий веер от центра
        var spread = (layer * STEP) * e;
        var dx = (DIR_X * spread + p.fanX * 3 * e) * scale + parX * layer;
        var dy = (DIR_Y * spread + p.fanY * 3 * e) * scale + parY * layer;
        var ang = p.rot * e;
        var sc = 1 + layer * 0.012 * e;

        var w = (d.w || 0) * scale;
        var h = (d.h || 0) * scale;
        var cxp = offX + (d.x + (d.w || 0) / 2) * scale + dx;
        var cyp = offY + (d.y + (d.h || 0) / 2) * scale + dy;

        ctx.save();
        ctx.translate(cxp, cyp);
        ctx.rotate(ang);
        ctx.scale(sc, sc);

        // тень появляется при расслоении — слои будто парят
        if (e > 0.01 && d.kind !== 'frame') {
            ctx.shadowColor = 'rgba(40,70,120,' + (0.28 * e) + ')';
            ctx.shadowBlur = 16 * e;
            ctx.shadowOffsetX = 3 * e;
            ctx.shadowOffsetY = 5 * e;
        }

        if (d.kind === 'dot') {
            ctx.fillStyle = d.fill;
            ctx.beginPath();
            ctx.arc(0, 0, w / 2, 0, Math.PI * 2);
            ctx.fill();
        } else if (d.kind === 'frame') {
            roundRect(-w / 2, -h / 2, w, h, (d.r || 0) * scale);
            ctx.lineWidth = 2;
            ctx.strokeStyle = d.stroke;
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(-w / 2, -h / 2 + 9 * scale);
            ctx.lineTo(w / 2, -h / 2 + 9 * scale);
            ctx.globalAlpha = 0.5;
            ctx.stroke();
        } else {
            roundRect(-w / 2, -h / 2, w, h, (d.r || 0) * scale);
            if (d.fill) { ctx.fillStyle = d.fill; ctx.fill(); }
            if (d.stroke) {
                ctx.shadowBlur = 0;
                ctx.lineWidth = 1.4;
                ctx.strokeStyle = d.stroke;
                ctx.stroke();
            }
        }
        ctx.restore();
    }

    function frame() {
        if (!running) return;
        // плавно тянемся к цели (наведение/уход)
        var target = hovering ? 1 : 0;
        e += (target - e) * 0.09;
        if (e < 0.001) e = 0;

        // лёгкий параллакс по курсору при наведении
        var parX = 0, parY = 0;
        if (hovering && e > 0.01) {
            parX = ((mouse.x - W / 2) / W) * 10 * e;
            parY = ((mouse.y - H / 2) / H) * 10 * e;
        }

        ctx.clearRect(0, 0, W, H);
        // от дальних слоёв к ближним
        for (var i = 0; i < pieces.length; i++) drawPiece(pieces[i], parX, parY);
        requestAnimationFrame(frame);
    }

    function staticFrame() {
        ctx.clearRect(0, 0, W, H);
        for (var i = 0; i < pieces.length; i++) drawPiece(pieces[i], 0, 0);
    }

    function setMouse(clientX, clientY) {
        var rect = canvas.getBoundingClientRect();
        mouse.x = clientX - rect.left;
        mouse.y = clientY - rect.top;
    }

    host.addEventListener('mouseenter', function () { hovering = true; });
    host.addEventListener('mouseleave', function () { hovering = false; });
    host.addEventListener('mousemove', function (ev) { setMouse(ev.clientX, ev.clientY); }, { passive: true });

    // На тач-устройствах: тап — расслоить, повторный тап — собрать
    host.addEventListener('touchstart', function (ev) {
        if (ev.touches && ev.touches.length) setMouse(ev.touches[0].clientX, ev.touches[0].clientY);
        hovering = !hovering;
    }, { passive: true });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            running = false;
        } else if (!reduceMotion) {
            running = true;
            requestAnimationFrame(frame);
        }
    });

    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            resize();
            if (reduceMotion) staticFrame();
        }, 150);
    });

    // --- Старт ---
    buildPieces();
    resize();
    if (reduceMotion) {
        staticFrame();
    } else {
        requestAnimationFrame(frame);
    }
})();
