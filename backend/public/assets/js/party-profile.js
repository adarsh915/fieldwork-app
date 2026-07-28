document.addEventListener('DOMContentLoaded', function () {

    // ── Tab Switching ────────────────────────────────────────
    const tabs = document.querySelectorAll('.profile-tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            // Re-init pagination for the active tab
            const tableId = document.querySelector('#tab-' + this.dataset.tab + ' table').id;
            initPagination(tableId);
        });
    });

    // ── Client-side Pagination ───────────────────────────────
    const PAGE_SIZE = 10;

    function initPagination(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const filteredRows = rows.filter(r => r.style.display !== 'none');
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));
        let currentPage = 1;

        function render() {
            filteredRows.forEach((row, i) => {
                row.style.display = (i >= (currentPage - 1) * PAGE_SIZE && i < currentPage * PAGE_SIZE) ? '' : 'none';
            });
            // Also keep truly hidden (search-filtered) rows hidden
            rows.filter(r => r.dataset.hidden === '1').forEach(r => r.style.display = 'none');

            const pgContainer = document.querySelector('.tab-pagination[data-table="' + tableId + '"]');
            if (!pgContainer) return;
            pgContainer.innerHTML = '';

            if (filteredRows.length === 0) {
                pgContainer.innerHTML = '<span class="pg-info">No records</span>';
                return;
            }

            const info = document.createElement('span');
            info.className = 'pg-info';
            info.textContent = 'Showing ' + ((currentPage - 1) * PAGE_SIZE + 1) + '–' + Math.min(currentPage * PAGE_SIZE, filteredRows.length) + ' of ' + filteredRows.length;
            pgContainer.appendChild(info);

            const prev = document.createElement('button');
            prev.textContent = 'Prev';
            prev.disabled = currentPage === 1;
            prev.addEventListener('click', () => { currentPage--; render(); });
            pgContainer.appendChild(prev);

            // Show max 5 page buttons around current
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, startPage + 4);
            for (let p = startPage; p <= endPage; p++) {
                const btn = document.createElement('button');
                btn.textContent = p;
                if (p === currentPage) btn.classList.add('pg-active');
                btn.addEventListener('click', () => { currentPage = p; render(); });
                pgContainer.appendChild(btn);
            }

            const next = document.createElement('button');
            next.textContent = 'Next';
            next.disabled = currentPage === totalPages;
            next.addEventListener('click', () => { currentPage++; render(); });
            pgContainer.appendChild(next);
        }

        render();
    }

    // ── Client-side Search ───────────────────────────────────
    document.querySelectorAll('.tab-search').forEach(input => {
        input.addEventListener('input', function () {
            const tableId = this.dataset.target;
            const table = document.getElementById(tableId);
            if (!table) return;
            const query = this.value.toLowerCase();
            const rows = Array.from(table.querySelectorAll('tbody tr'));

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.includes(query);
                row.style.display = match ? '' : 'none';
                row.dataset.hidden = match ? '0' : '1';
            });

            initPagination(tableId);
        });
    });

    // Init pagination for the default active tab
    initPagination('visits-table');
});
