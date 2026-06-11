/*
 * Парящие 3D-фигуры в фоне hero-блока.
 * Каркасные многогранники с настоящим вращением в 3D и перспективой,
 * реагируют на курсор: разбегаются и ускоряют вращение рядом с ним.
 * Чистый canvas 2D, без библиотек. ~ несколько КБ.
 */
(function () {
    'use strict';

    var canvas = document.getElementById('hero3d');
    if (!canvas) return;

    var host = canvas.parentElement;
    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Палитра в тон сайту
    var COLORS = ['#4a6fa5', '#6f9bd1', '#3a5a8a', '#8bb4e0', '#c4dcfa'];

    // ---- Геометрия многогранников (вершины + рёбра) ----
    var SHAPES = {
        cube: {
            v: [
                [-1, -1, -1], [1, -1, -1], [1, 1, -1], [-1, 1, -1],
                [-1, -1, 1], [1, -1, 1], [1, 1, 1], [-1, 1, 1]
            ],
            e: [
                [0, 1], [1, 2], [2, 3], [3, 0],
                [4, 5], [5, 6], [6, 7], [7, 4],
                [0, 4], [1, 5], [2, 6], [3, 7]
            ]
        },
        octa: {
            v: [
                [1, 0, 0], [-1, 0, 0], [0, 1, 0],
                [0, -1, 0], [0, 0, 1], [0, 0, -1]
            ],
            e: [
                [0, 2], [0, 3], [0, 4], [0, 5],
                [1, 2], [1, 3], [1, 4], [1, 5],
                [2, 4], [4, 3], [3, 5], [5, 2]
            ]
        },
        tetra: {
            v: [
                [1, 1, 1], [1, -1, -1], [-1, 1, -1], [-1, -1, 1]
            ],
            e: [
                [0, 1], [0, 2], [0, 3], [1, 2], [1, 3], [2, 3]
            ]
        }
    };
    var SHAPE_KEYS = ['cube', 'octa', 'tetra'];

    var FOCAL = 320;        // фокусное расстояние для перспективы
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var W = 0, H = 0;
    var shapes = [];
    var mouse = { x: -9999, y: -9999, active: false };
    var running = true;

    function rand(min, max) { return min + Math.random() * (max - min); }

    function makeShape() {
        var key = SHAPE_KEYS[(Math.random() * SHAPE_KEYS.length) | 0];
        return {
            geo: SHAPES[key],
            x: rand(0, W),
            y: rand(0, H),
            size: rand(26, 58),
            color: COLORS[(Math.random() * COLORS.length) | 0],
            depth: rand(0.45, 1),          // влияет на прозрачность/толщину
            rx: rand(0, Math.PI * 2),
            ry: rand(0, Math.PI * 2),
            rz: rand(0, Math.PI * 2),
            // базовая скорость вращения
            vrx: rand(-0.006, 0.006),
            vry: rand(-0.006, 0.006),
            vrz: rand(-0.004, 0.004),
            // дрейф
            vx: rand(-0.25, 0.25),
            vy: rand(-0.25, 0.25),
            spin: 0                         // дополнительный «толчок» вращения от курсора
        };
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

        // плотность фигур зависит от площади, но в разумных пределах
        var target = Math.round(Math.min(14, Math.max(7, (W * H) / 90000)));
        if (shapes.length === 0) {
            for (var i = 0; i < target; i++) shapes.push(makeShape());
        } else if (shapes.length < target) {
            while (shapes.length < target) shapes.push(makeShape());
        } else if (shapes.length > target) {
            shapes.length = target;
        }
    }

    // Поворот точки по трём осям
    function rotate(p, rx, ry, rz) {
        var x = p[0], y = p[1], z = p[2];
        var cy = Math.cos(rx), sy = Math.sin(rx);
        var y1 = y * cy - z * sy;
        var z1 = y * sy + z * cy;
        var cx = Math.cos(ry), sx = Math.sin(ry);
        var x1 = x * cx + z1 * sx;
        var z2 = -x * sx + z1 * cx;
        var cz = Math.cos(rz), sz = Math.sin(rz);
        var x2 = x1 * cz - y1 * sz;
        var y2 = x1 * sz + y1 * cz;
        return [x2, y2, z2];
    }

    function drawShape(s) {
        var geo = s.geo;
        var verts = geo.v;
        var proj = [];
        for (var i = 0; i < verts.length; i++) {
            var r = rotate(verts[i], s.rx, s.ry, s.rz);
            var zz = r[2] * s.size;
            var scale = FOCAL / (FOCAL - zz);
            proj.push([
                s.x + r[0] * s.size * scale,
                s.y + r[1] * s.size * scale,
                zz
            ]);
        }

        var alpha = 0.32 + s.depth * 0.45;
        ctx.lineWidth = 1 + s.depth * 0.9;
        ctx.strokeStyle = hexToRgba(s.color, alpha);
        ctx.beginPath();
        var edges = geo.e;
        for (var j = 0; j < edges.length; j++) {
            var a = proj[edges[j][0]];
            var b = proj[edges[j][1]];
            ctx.moveTo(a[0], a[1]);
            ctx.lineTo(b[0], b[1]);
        }
        ctx.stroke();

        // светящиеся вершины
        ctx.fillStyle = hexToRgba(s.color, Math.min(1, alpha + 0.25));
        for (var k = 0; k < proj.length; k++) {
            var v = proj[k];
            var rad = 1.4 + s.depth * 1.6 + (v[2] > 0 ? 0.8 : 0);
            ctx.beginPath();
            ctx.arc(v[0], v[1], rad, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    function update(s) {
        s.rx += s.vrx + s.spin;
        s.ry += s.vry + s.spin * 0.8;
        s.rz += s.vrz;
        s.spin *= 0.94; // затухание толчка

        s.x += s.vx;
        s.y += s.vy;

        // реакция на курсор — мягкое отталкивание
        if (mouse.active) {
            var dx = s.x - mouse.x;
            var dy = s.y - mouse.y;
            var dist2 = dx * dx + dy * dy;
            var R = 170;
            if (dist2 < R * R && dist2 > 0.01) {
                var dist = Math.sqrt(dist2);
                var force = (1 - dist / R) * 1.8;
                s.vx += (dx / dist) * force;
                s.vy += (dy / dist) * force;
                s.spin += force * 0.0016; // рядом с курсором крутятся быстрее
            }
        }

        // лёгкое трение, чтобы скорость не накапливалась
        s.vx *= 0.96;
        s.vy *= 0.96;

        // поддерживаем минимальный дрейф, чтобы сцена всегда «жила»
        if (Math.abs(s.vx) < 0.12) s.vx += (s.vx >= 0 ? 0.01 : -0.01);
        if (Math.abs(s.vy) < 0.12) s.vy += (s.vy >= 0 ? 0.01 : -0.01);

        // оборачивание по краям
        var m = s.size * 1.6;
        if (s.x < -m) s.x = W + m;
        else if (s.x > W + m) s.x = -m;
        if (s.y < -m) s.y = H + m;
        else if (s.y > H + m) s.y = -m;
    }

    function frame() {
        if (!running) return;
        ctx.clearRect(0, 0, W, H);
        for (var i = 0; i < shapes.length; i++) {
            update(shapes[i]);
            drawShape(shapes[i]);
        }
        requestAnimationFrame(frame);
    }

    function staticFrame() {
        ctx.clearRect(0, 0, W, H);
        for (var i = 0; i < shapes.length; i++) drawShape(shapes[i]);
    }

    function hexToRgba(hex, a) {
        var h = hex.replace('#', '');
        var r = parseInt(h.substring(0, 2), 16);
        var g = parseInt(h.substring(2, 4), 16);
        var b = parseInt(h.substring(4, 6), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
    }

    // ---- Указатель ----
    function pointerMove(clientX, clientY) {
        var rect = canvas.getBoundingClientRect();
        var x = clientX - rect.left;
        var y = clientY - rect.top;
        if (x >= -60 && x <= W + 60 && y >= -60 && y <= H + 60) {
            mouse.x = x;
            mouse.y = y;
            mouse.active = true;
        } else {
            mouse.active = false;
        }
    }

    window.addEventListener('mousemove', function (e) {
        pointerMove(e.clientX, e.clientY);
    }, { passive: true });

    window.addEventListener('touchmove', function (e) {
        if (e.touches && e.touches.length) {
            pointerMove(e.touches[0].clientX, e.touches[0].clientY);
        }
    }, { passive: true });

    window.addEventListener('mouseout', function () { mouse.active = false; });
    window.addEventListener('touchend', function () { mouse.active = false; });

    // Клик — «разлёт» ближайших фигур для интерактивности
    host.addEventListener('click', function (e) {
        var rect = canvas.getBoundingClientRect();
        var cx = e.clientX - rect.left;
        var cy = e.clientY - rect.top;
        for (var i = 0; i < shapes.length; i++) {
            var s = shapes[i];
            var dx = s.x - cx, dy = s.y - cy;
            var dist = Math.sqrt(dx * dx + dy * dy) || 1;
            if (dist < 260) {
                var push = (1 - dist / 260) * 9;
                s.vx += (dx / dist) * push;
                s.vy += (dy / dist) * push;
                s.spin += 0.05;
            }
        }
    });

    // Пауза, когда вкладка неактивна
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

    // ---- Старт ----
    resize();
    if (reduceMotion) {
        staticFrame(); // без движения — просто статичная композиция
    } else {
        requestAnimationFrame(frame);
    }
})();
