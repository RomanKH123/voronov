'use strict';

window.workOrderDirty = false;
window.initWorkOrder = function (list, works) {
    document.getElementById('work-order-toolbar')?.remove();
    window.workOrderDirty = false;
    if (!works.length) return;

    const toolbar = document.createElement('div');
    toolbar.id = 'work-order-toolbar';
    toolbar.className = 'work-order-toolbar';
    const help = document.createElement('p');
    help.textContent = 'Перетащите работу за ↕ или используйте стрелки. Первые 6 работ показываются на главной и страницах услуг.';
    const save = document.createElement('button');
    save.type = 'button'; save.className = 'btn btn-primary';
    save.textContent = 'Сохранить порядок'; save.disabled = true;
    const status = document.createElement('span');
    status.setAttribute('role', 'status');
    toolbar.append(help, save, status);
    list.before(toolbar);

    let saving = false;
    let dragging = null;
    const rows = () => [...list.querySelectorAll('.table-row[data-id]')];
    const ids = () => rows().map(row => Number(row.dataset.id));
    let saved = JSON.stringify(ids());
    function refresh() {
        window.workOrderDirty = saved !== JSON.stringify(ids());
        save.disabled = saving || !window.workOrderDirty;
        rows().forEach((row, i, all) => {
            row.querySelector('.work-position').textContent = String(i + 1);
            row.querySelector('[data-move="up"]').disabled = saving || i === 0;
            row.querySelector('[data-move="down"]').disabled = saving || i === all.length - 1;
            row.querySelector('.work-drag').draggable = !saving;
        });
    }
    function changed() {
        refresh();
        status.textContent = window.workOrderDirty ? 'Порядок изменён — сохраните его.' : '';
    }
    rows().forEach(row => {
        const controls = document.createElement('div');
        controls.className = 'work-order-controls';
        const handle = document.createElement('span');
        handle.className = 'work-drag'; handle.textContent = '↕';
        handle.title = 'Перетащить работу'; handle.draggable = true;
        const position = document.createElement('span');
        position.className = 'work-position';
        const title = row.querySelector('.table-title')?.textContent || 'Работа';
        controls.append(handle, position);
        for (const [direction, label, arrow] of [['up', 'Поднять', '↑'], ['down', 'Опустить', '↓']]) {
            const button = document.createElement('button');
            button.type = 'button'; button.dataset.move = direction; button.textContent = arrow;
            button.setAttribute('aria-label', label + ': ' + title);
            button.addEventListener('click', () => {
                if (saving) return;
                const neighbor = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
                if (!neighbor) return;
                if (direction === 'up') list.insertBefore(row, neighbor);
                else list.insertBefore(neighbor, row);
                changed();
                if (!button.disabled) button.focus();
                else row.querySelector(direction === 'up' ? '[data-move="down"]' : '[data-move="up"]').focus();
            });
            controls.append(button);
        }
        row.querySelector('.table-title').after(controls);
        handle.addEventListener('dragstart', event => {
            if (saving) { event.preventDefault(); return; }
            dragging = row;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', row.dataset.id);
            row.classList.add('work-dragging');
        });
        handle.addEventListener('dragend', () => {
            dragging = null; row.classList.remove('work-dragging');
        });
        row.addEventListener('dragover', event => {
            if (dragging && !saving) { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; }
        });
        row.addEventListener('drop', event => {
            if (!dragging || dragging === row || saving) return;
            event.preventDefault();
            const after = event.clientY > row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2;
            list.insertBefore(dragging, after ? row.nextElementSibling : row);
            changed();
        });
    });
    save.addEventListener('click', async () => {
        if (saving) return;
        saving = true; refresh(); status.textContent = 'Сохраняем…';
        try {
            const response = await fetch('/CMM/api.php?action=reorder', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ids: ids()})
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Не удалось сохранить порядок.');
            saved = JSON.stringify(ids());
            status.textContent = 'Порядок сохранён.';
        } catch (error) {
            status.textContent = error.message || 'Ошибка соединения. Повторите сохранение.';
        } finally {
            saving = false; refresh();
        }
    });
    refresh();
};

window.addEventListener('beforeunload', event => {
    if (window.workOrderDirty) { event.preventDefault(); event.returnValue = ''; }
});
