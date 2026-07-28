/**
 * Admin UI (frontend-only)
 * - Mock data stored in localStorage
 * - Table search, add/edit/delete via modal
 * - Works per-page using data attributes in Blade views
 */

function qs(sel, root = document) {
  return root.querySelector(sel);
}

function qsa(sel, root = document) {
  return Array.from(root.querySelectorAll(sel));
}

function uid() {
  return Math.random().toString(16).slice(2) + Date.now().toString(16);
}

function storageKey(moduleKey) {
  return `ampl_admin_ui:${moduleKey}`;
}

function loadRows(moduleKey, seedRows) {
  const raw = localStorage.getItem(storageKey(moduleKey));
  if (raw) {
    try {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) return parsed;
    } catch {
      // ignore
    }
  }
  const seeded = (seedRows ?? []).map((r) => ({ id: r.id ?? uid(), ...r }));
  localStorage.setItem(storageKey(moduleKey), JSON.stringify(seeded));
  return seeded;
}

function saveRows(moduleKey, rows) {
  localStorage.setItem(storageKey(moduleKey), JSON.stringify(rows));
}

function createModal() {
  const modal = qs('[data-ui-modal]');
  if (!modal) return null;

  const backdrop = modal;
  const closeBtns = qsa('[data-ui-modal-close]', modal);

  function open() {
    backdrop.classList.remove('hidden');
    backdrop.classList.add('flex');
  }

  function close() {
    backdrop.classList.add('hidden');
    backdrop.classList.remove('flex');
  }

  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) close();
  });
  closeBtns.forEach((btn) => btn.addEventListener('click', close));

  return { open, close, root: modal };
}

function renderTable({ tableEl, columns, rows, onEdit, onDelete }) {
  const tbody = qs('tbody', tableEl);
  if (!tbody) return;
  tbody.innerHTML = '';

  if (!rows.length) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="py-10 text-center text-slate-500" colspan="${columns.length + 1}">No records</td>`;
    tbody.appendChild(tr);
    return;
  }

  rows.forEach((row) => {
    const tr = document.createElement('tr');
    const cells = columns
      .map((c) => {
        const v = row[c.key] ?? '';
        return `<td>${escapeHtml(String(v))}</td>`;
      })
      .join('');

    tr.innerHTML = `
      ${cells}
      <td class="whitespace-nowrap">
        <div class="flex items-center justify-end gap-2">
          <button class="btn-secondary px-2.5 py-1.5 text-xs" data-ui-edit="${row.id}">Edit</button>
          <button class="btn-secondary border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs text-rose-700 hover:bg-rose-100" data-ui-del="${row.id}">Delete</button>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
  });

  qsa('[data-ui-edit]', tableEl).forEach((btn) => {
    btn.addEventListener('click', () => onEdit(btn.getAttribute('data-ui-edit')));
  });
  qsa('[data-ui-del]', tableEl).forEach((btn) => {
    btn.addEventListener('click', () => onDelete(btn.getAttribute('data-ui-del')));
  });
}

function escapeHtml(str) {
  return str.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}

function initCrudTable() {
  const root = qs('[data-ui-module]');
  if (!root) return;

  const moduleKey = root.getAttribute('data-ui-module');
  const seed = root.getAttribute('data-ui-seed');
  const columnsAttr = root.getAttribute('data-ui-columns');
  if (!moduleKey || !columnsAttr) return;

  const columns = columnsAttr.split('|').map((part) => {
    const [key, label] = part.split(':');
    return { key: key.trim(), label: (label ?? key).trim() };
  });

  const seedRows = seed ? JSON.parse(seed) : [];
  let rows = loadRows(moduleKey, seedRows);

  const tableEl = qs('[data-ui-table]');
  const searchEl = qs('[data-ui-search]');
  const addBtn = qs('[data-ui-add]');

  const modal = createModal();
  const form = modal ? qs('form[data-ui-form]', modal.root) : null;
  const formTitle = modal ? qs('[data-ui-form-title]', modal.root) : null;

  let editingId = null;

  function filteredRows() {
    const q = (searchEl?.value ?? '').trim().toLowerCase();
    if (!q) return rows;
    return rows.filter((r) => columns.some((c) => String(r[c.key] ?? '').toLowerCase().includes(q)));
  }

  function refresh() {
    if (!tableEl) return;
    renderTable({
      tableEl,
      columns,
      rows: filteredRows(),
      onEdit: (id) => openEdit(id),
      onDelete: (id) => doDelete(id),
    });
  }

  function openAdd() {
    editingId = null;
    if (formTitle) formTitle.textContent = 'Add Record';
    if (form) {
      form.reset();
      qs('[name="id"]', form).value = '';
    }
    modal?.open();
  }

  function openEdit(id) {
    const row = rows.find((r) => String(r.id) === String(id));
    if (!row || !form) return;
    editingId = row.id;
    if (formTitle) formTitle.textContent = 'Edit Record';
    qs('[name="id"]', form).value = row.id;
    columns.forEach((c) => {
      const el = qs(`[name="${c.key}"]`, form);
      if (el) el.value = row[c.key] ?? '';
    });
    modal?.open();
  }

  function doDelete(id) {
    const yes = confirm('Delete this record?');
    if (!yes) return;
    rows = rows.filter((r) => String(r.id) !== String(id));
    saveRows(moduleKey, rows);
    refresh();
  }

  function upsert(formData) {
    const next = {};
    columns.forEach((c) => (next[c.key] = formData.get(c.key) ?? ''));

    if (editingId) {
      rows = rows.map((r) => (String(r.id) === String(editingId) ? { ...r, ...next } : r));
    } else {
      rows = [{ id: uid(), ...next }, ...rows];
    }
    saveRows(moduleKey, rows);
    refresh();
  }

  // initial header render
  if (tableEl) {
    const theadTr = qs('thead tr', tableEl);
    if (theadTr) {
      theadTr.innerHTML =
        columns.map((c) => `<th>${escapeHtml(c.label)}</th>`).join('') +
        `<th class="text-right">Actions</th>`;
    }
  }

  refresh();

  searchEl?.addEventListener('input', refresh);
  addBtn?.addEventListener('click', openAdd);

  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    upsert(fd);
    modal?.close();
  });
}

function initDealerMenu() {
  const root = qs('[data-ui-dealer-menu]');
  if (!root) return;

  const overlay = qs('[data-ui-dealer-overlay]');
  const card = qs('[data-ui-dealer-card]');
  const close = () => overlay?.classList.add('hidden');
  const open = () => overlay?.classList.remove('hidden');

  overlay?.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });
  qs('[data-ui-dealer-close]')?.addEventListener('click', close);

  qsa('[data-ui-dealer-open]').forEach((btn) => btn.addEventListener('click', open));

  // prevent overlay click-through
  card?.addEventListener('click', (e) => e.stopPropagation());
}

function initDayStartMock() {
  const root = qs('[data-ui-daystart]');
  if (!root) return;

  const partyBtn = qs('[data-ui-party-open]');
  const partyOverlay = qs('[data-ui-party-overlay]');
  const partyCloseBtn = qs('[data-ui-party-close]');
  const search = qs('[data-ui-party-search]');
  const list = qs('[data-ui-party-list]');
  const selectedOut = qs('[data-ui-party-selected]');

  const parties = [
    '292 - 20 MICRONS LIMITED - BARODA - VADODDARA',
    '101 - AMPL INDUSTRIES - JABALPUR',
    '205 - SUPER TRADERS - INDORE',
    '334 - ALPHA DISTRIBUTORS - BHOPAL',
    '410 - OM ENTERPRISES - UJJAIN',
  ];
  let selected = new Set();

  function render(q = '') {
    const qq = q.trim().toLowerCase();
    const items = qq ? parties.filter((p) => p.toLowerCase().includes(qq)) : parties;
    list.innerHTML = '';
    items.forEach((p) => {
      const checked = selected.has(p);
      const row = document.createElement('button');
      row.type = 'button';
      row.className =
        'w-full flex items-center gap-3 border-b border-slate-100 px-4 py-3 text-left hover:bg-slate-50';
      row.innerHTML = `
        <span class="inline-flex h-5 w-5 items-center justify-center rounded border ${checked ? 'border-[var(--ampl-primary)] bg-[var(--ampl-primary)] text-white' : 'border-slate-300 bg-white text-transparent'}">
          ✓
        </span>
        <span class="text-sm font-bold text-[var(--ampl-primary)]">${escapeHtml(p)}</span>
      `;
      row.addEventListener('click', () => {
        if (selected.has(p)) selected.delete(p);
        else selected.add(p);
        render(search.value);
        selectedOut.textContent = selected.size ? `${selected.size} selected` : '0 selected';
      });
      list.appendChild(row);
    });
  }

  const open = () => {
    partyOverlay.classList.remove('hidden');
    render(search.value);
  };
  const close = () => partyOverlay.classList.add('hidden');

  partyBtn?.addEventListener('click', open);
  partyCloseBtn?.addEventListener('click', close);
  partyOverlay?.addEventListener('click', (e) => {
    if (e.target === partyOverlay) close();
  });
  search?.addEventListener('input', () => render(search.value));

  // init
  selectedOut.textContent = '0 selected';
}

document.addEventListener('DOMContentLoaded', () => {
  initCrudTable();
  initDealerMenu();
  initDayStartMock();
});

