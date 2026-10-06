'use strict';

const applications = JSON.parse(document.getElementById('applicationsData').textContent);
const byId = new Map(applications.map(app => [String(app.id), app]));
const rows = [...document.querySelectorAll('#applicationsTable tbody tr')];
const controls = Object.fromEntries(['search', 'period', 'sort', 'pageSize'].map(id => [id, document.getElementById(id)]));
const defaults = { search: '', period: 'all', sort: 'priority', pageSize: '20', status: 'all', page: 1 };
const storageKey = 'admin-applications-view-v1';
let state = { ...defaults };
try { state = { ...state, ...JSON.parse(sessionStorage.getItem(storageKey) || '{}') }; } catch { /* Storage may be unavailable. */ }
if (!['all', 'new', 'processed', 'completed'].includes(state.status)) state.status = 'all';
state.page = Math.max(1, parseInt(state.page, 10) || 1);
for (const [key, control] of Object.entries(controls)) {
    control.value = typeof state[key] === 'string' ? state[key] : defaults[key];
    if (control.tagName === 'SELECT' && !control.value) control.value = defaults[key];
    state[key] = control.value;
}

const statusLabels = { new: 'Новая', processed: 'В обработке', completed: 'Завершена' };
const priorities = { new: 0, processed: 1, completed: 2 };
const normalize = value => String(value ?? '').toLocaleLowerCase('ru').replace(/ё/g, 'е');
const timestamps = new Map(applications.map(app => [String(app.id), new Date(String(app.created_at).replace(' ', 'T')).getTime()]));
const searchIndex = new Map(applications.map(app => [String(app.id), normalize([app.id, app.name, app.phone, app.email, app.message].join(' '))]));
const selectPage = document.getElementById('selectPage');
let visibleRows = [];
let draggedId = null;

function renderBoard(groups, start, pageSize) {
    document.getElementById('kanban').hidden = false;
    document.getElementById('applicationsTable').hidden = true;
    document.querySelectorAll('.kanban-column').forEach(column => {
        const status = column.dataset.stage;
        const group = groups[status];
        column.querySelector('.stage-count').textContent = group.length;
        const container = column.querySelector('.stage-cards');
        container.replaceChildren();
        group.slice(start, start + pageSize).forEach(row => {
            const app = byId.get(row.dataset.id);
            const card = document.createElement('article');
            card.className = 'lead-card';
            card.dataset.id = row.dataset.id;
            card.draggable = true;
            const top = document.createElement('div');
            top.className = 'card-top';
            const select = document.createElement('input');
            select.type = 'checkbox'; select.className = 'card-select';
            select.setAttribute('aria-label', `Выбрать заявку №${app.id}`);
            select.addEventListener('change', () => { row.querySelector('.row-select').checked = select.checked; updateSelection(); });
            const reference = document.createElement('span'); reference.textContent = `№${app.id}`;
            const date = document.createElement('time'); date.textContent = row.querySelector('.date-cell').textContent;
            top.append(select, reference, date);
            const title = document.createElement('button');
            title.type = 'button'; title.className = 'card-title'; title.textContent = app.name || 'Без имени';
            title.addEventListener('click', () => viewDetails(app.id));
            const contact = document.createElement('div'); contact.className = 'card-contacts';
            ['.phone-cell a', '.email-cell a'].forEach(selector => {
                const link = row.querySelector(selector); if (link) contact.appendChild(link.cloneNode(true));
            });
            const message = document.createElement('p'); message.className = 'card-message'; message.textContent = app.message || 'Без сообщения';
            const footer = document.createElement('div'); footer.className = 'card-footer';
            footer.appendChild(row.querySelector('.status-cell form').cloneNode(true));
            const details = document.createElement('button'); details.type = 'button'; details.textContent = 'Открыть'; details.addEventListener('click', () => viewDetails(app.id));
            footer.appendChild(details);
            card.append(top, title, contact, message, footer);
            card.addEventListener('dragstart', event => {
                if (event.target.closest('a, button, input, select')) { event.preventDefault(); return; }
                draggedId = String(app.id); event.dataTransfer.setData('text/plain', draggedId); event.dataTransfer.effectAllowed = 'move'; card.classList.add('dragging');
            });
            card.addEventListener('dragend', () => { draggedId = null; card.classList.remove('dragging'); document.querySelectorAll('.drop-target').forEach(el => el.classList.remove('drop-target')); });
            container.appendChild(card);
        });
        if (!container.children.length) {
            const empty = document.createElement('p'); empty.className = 'column-empty';
            empty.textContent = group.length ? 'На этой странице карточек нет' : 'Здесь пока нет заявок';
            container.appendChild(empty);
        }
    });
}
document.querySelectorAll('.kanban-column').forEach(column => {
    column.addEventListener('dragover', event => { if (draggedId) { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; column.classList.add('drop-target'); } });
    column.addEventListener('dragleave', event => { if (!column.contains(event.relatedTarget)) column.classList.remove('drop-target'); });
    column.addEventListener('drop', event => {
        event.preventDefault(); column.classList.remove('drop-target');
        const app = byId.get(draggedId);
        if (!app || app.status === column.dataset.stage) return;
        const form = document.getElementById('moveForm');
        form.elements.id.value = app.id; form.elements.status.value = column.dataset.stage;
        form.requestSubmit();
    });
});

function updateSelection() {
    const count = visibleRows.filter(row => row.querySelector('.row-select').checked).length;
    document.getElementById('selectionCount').textContent = `Выбрано: ${count}` + (count > 100 ? ' · максимум 100' : '');
    document.getElementById('bulkSubmit').disabled = count === 0 || count > 100;
    document.getElementById('clearSelection').disabled = count === 0;
    selectPage.checked = count > 0 && count === visibleRows.length;
    selectPage.indeterminate = count > 0 && count < visibleRows.length;
    selectPage.disabled = visibleRows.length === 0;
    visibleRows.forEach(row => row.classList.toggle('selected', row.querySelector('.row-select').checked));
    document.querySelectorAll('.lead-card').forEach(card => {
        const checked = rows.find(row => row.dataset.id === card.dataset.id).querySelector('.row-select').checked;
        card.classList.toggle('selected', checked);
        card.querySelector('.card-select').checked = checked;
    });
}

function render() {
    document.getElementById('bulkSubmit').textContent = 'Применить к выбранным';
    const query = normalize(state.search).trim();
    const phoneQuery = query.replace(/\D/g, '');
    const isPhoneQuery = phoneQuery.length > 0 && /^[\d\s()+-]+$/.test(query);
    const cutoff = new Date();
    cutoff.setHours(0, 0, 0, 0);
    if (state.period === 'week') cutoff.setDate(cutoff.getDate() - 6);
    if (state.period === 'month') cutoff.setDate(cutoff.getDate() - 29);
    const tomorrow = new Date();
    tomorrow.setHours(24, 0, 0, 0);
    const matches = rows.filter(row => {
        const app = byId.get(row.dataset.id);
        const time = timestamps.get(row.dataset.id);
        return (state.status === 'all' || app.status === state.status)
            && (state.period === 'all' || (time >= cutoff.getTime() && time < tomorrow.getTime()))
            && (!query || searchIndex.get(row.dataset.id).includes(query)
                || (isPhoneQuery && String(app.phone ?? '').replace(/\D/g, '').includes(phoneQuery)));
    }).sort((a, b) => {
        const priority = state.sort === 'priority' ? (priorities[a.dataset.status] ?? 3) - (priorities[b.dataset.status] ?? 3) : 0;
        const date = (timestamps.get(b.dataset.id) || 0) - (timestamps.get(a.dataset.id) || 0);
        return priority || (state.sort === 'oldest' ? -date : date) || Number(b.dataset.id) - Number(a.dataset.id);
    });
    const pageSize = Number(state.pageSize);
    const groups = Object.fromEntries(Object.keys(statusLabels).map(status => [status, matches.filter(row => row.dataset.status === status)]));
    const pages = Math.max(1, ...Object.values(groups).map(group => Math.ceil(group.length / pageSize)));
    state.page = Math.min(state.page, pages);
    const start = (state.page - 1) * pageSize;
    rows.forEach(row => {
        row.hidden = true;
        row.querySelector('.row-select').checked = false;
        row.classList.remove('selected');
    });
    visibleRows = Object.values(groups).flatMap(group => group.slice(start, start + pageSize));
    const body = document.querySelector('#applicationsTable tbody');
    visibleRows.forEach(row => { row.hidden = false; body.appendChild(row); });
    renderBoard(groups, start, pageSize);
    document.querySelectorAll('.filter-btn').forEach(button => {
        const active = button.dataset.filter === state.status;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('[data-summary]').forEach(button => {
        const active = button.dataset.summary === 'today'
            ? state.period === 'today' : button.dataset.summary === state.status;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
    });
    document.getElementById('resultCount').textContent = matches.length
        ? `На доске: ${visibleRows.length} из ${matches.length} · Всего заявок: ${rows.length}`
        : `Найдено: 0 · Всего заявок: ${rows.length}`;
    document.getElementById('pageInfo').textContent = `${state.page} / ${pages}`;
    document.getElementById('prevPage').disabled = state.page === 1;
    document.getElementById('nextPage').disabled = state.page === pages;
    document.getElementById('emptyState').hidden = matches.length > 0;
    if (!rows.length) {
        document.getElementById('emptyTitle').textContent = 'Заявок пока нет';
        document.getElementById('emptyText').textContent = 'Новые обращения с сайта появятся здесь.';
        document.getElementById('emptyReset').hidden = true;
    }
    updateSelection();
    try { sessionStorage.setItem(storageKey, JSON.stringify(state)); } catch { /* Keep working without storage. */ }
}

Object.entries(controls).forEach(([key, control]) => {
    control.addEventListener(key === 'search' ? 'input' : 'change', () => {
        state[key] = control.value;
        state.page = 1;
        render();
    });
});
document.querySelectorAll('.filter-btn').forEach(button => button.addEventListener('click', () => {
    state.status = button.dataset.filter;
    state.page = 1;
    render();
}));
document.querySelectorAll('[data-summary]').forEach(button => button.addEventListener('click', () => {
    if (button.dataset.summary === 'today') {
        state.period = controls.period.value = state.period === 'today' ? 'all' : 'today';
    } else {
        state.status = button.dataset.summary;
    }
    state.page = 1;
    render();
}));
function resetFilters() {
    state = { ...defaults };
    Object.entries(controls).forEach(([key, control]) => { control.value = state[key]; });
    render();
    controls.search.focus();
}
document.getElementById('resetFilters').addEventListener('click', resetFilters);
document.getElementById('emptyReset').addEventListener('click', resetFilters);
document.getElementById('prevPage').addEventListener('click', () => { state.page--; render(); });
document.getElementById('nextPage').addEventListener('click', () => { state.page++; render(); });
selectPage.addEventListener('change', () => {
    visibleRows.forEach(row => { row.querySelector('.row-select').checked = selectPage.checked; });
    updateSelection();
});
rows.forEach(row => row.querySelector('.row-select').addEventListener('change', updateSelection));
document.getElementById('clearSelection').addEventListener('click', () => {
    visibleRows.forEach(row => { row.querySelector('.row-select').checked = false; });
    updateSelection();
});
document.getElementById('bulkForm').addEventListener('submit', event => {
    const count = visibleRows.filter(row => row.querySelector('.row-select').checked).length;
    if (!count || count > 100) { event.preventDefault(); return; }
    const button = document.getElementById('bulkSubmit');
    button.disabled = true;
    button.textContent = 'Сохраняем…';
});

let returnFocus = null;
function openModal(id) {
    if (!document.querySelector('.modal.show')) returnFocus = document.activeElement;
    document.querySelectorAll('.modal.show').forEach(modal => modal.classList.remove('show'));
    const modal = document.getElementById(id);
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    (modal.querySelector('.cancel') || modal).focus();
}
function closeDialogs() {
    document.querySelectorAll('.modal.show').forEach(modal => modal.classList.remove('show'));
    document.body.classList.remove('modal-open');
    if (returnFocus) returnFocus.focus();
}
function showMessage(id) {
    const app = byId.get(String(id));
    if (!app) return;
    document.getElementById('messageContent').textContent = app.message || 'Сообщение отсутствует';
    openModal('messageModal');
}
function viewDetails(id) {
    const app = byId.get(String(id));
    if (!app) return;
    document.getElementById('detailsTitle').textContent = `Заявка №${app.id}`;
    document.getElementById('detailsDelete').dataset.id = app.id;
    const container = document.getElementById('detailsContent');
    container.replaceChildren();
    const fields = [
        ['Статус', statusLabels[app.status] || app.status], ['Имя', app.name], ['Телефон', app.phone, 'tel:'],
        ['Email', app.email, 'mailto:'], ['Сообщение', app.message],
        ['Форма', app.form_type === 'modal' ? 'Модальная' : 'Основная'],
        ['Дата', new Date(timestamps.get(String(id))).toLocaleString('ru-RU')], ['IP адрес', app.ip_address]
    ];
    fields.forEach(([label, value, scheme]) => {
        const heading = document.createElement('div');
        heading.className = 'modal-label';
        heading.textContent = label;
        const content = document.createElement('div');
        content.className = 'modal-value';
        if (scheme && value) {
            const link = document.createElement('a');
            link.href = scheme + String(value).replace(/[\r\n]/g, '');
            link.textContent = value;
            content.appendChild(link);
        } else content.textContent = value || '—';
        container.append(heading, content);
    });
    openModal('detailsModal');
}
function deleteApplication(id) {
    const app = byId.get(String(id));
    if (!app) return;
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteDescription').textContent = `Удалить заявку №${id} от ${app.name || 'клиента'}?`;
    openModal('deleteModal');
}
function closeMessageModal() { closeDialogs(); }
function closeDetailsModal() { closeDialogs(); }
function closeModal() { closeDialogs(); }
document.querySelectorAll('.modal').forEach(modal => modal.addEventListener('click', event => {
    if (event.target === modal) closeDialogs();
}));
document.addEventListener('keydown', event => {
    const modal = document.querySelector('.modal.show');
    if (!modal) return;
    if (event.key === 'Escape') closeDialogs();
    if (event.key === 'Tab') {
        const focusable = [...modal.querySelectorAll('a[href], button, input:not([type="hidden"]), select, [tabindex="0"]')].filter(el => !el.disabled);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
});
window.addEventListener('pageshow', render);
render();
const requestedLead = new URLSearchParams(window.location.search).get('lead');
if (requestedLead && byId.has(requestedLead)) viewDetails(requestedLead);
