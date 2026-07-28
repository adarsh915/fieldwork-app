import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';
import Persist from '@alpinejs/persist';

Alpine.plugin(Persist);

// Make Alpine globally accessible (for legacy inline x-data on Blade pages)
window.Alpine = Alpine;
Alpine.start();

// DataTables (imported here so Vite bundles them; pages that need DT
// should call `import('datatables.net-dt')` lazily or rely on this init)
import DataTable from 'datatables.net-dt';

// Export to global for inline scripts that call `new DataTable(...)` or
// `$('#table').DataTable(...)` — works with vanilla JS approach.
window.DataTable = DataTable;

// Admin UI helpers (mock localStorage CRUD for frontend-only pages)
import './admin-ui';
