/**
 * The ALT HQ - Admin Panel Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // Mobile Sidebar Toggle
    const sidebar = document.getElementById('admin-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 960 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // Drag-and-drop visual indicator for dropzones
    document.querySelectorAll('.upload-dropzone').forEach(zone => {
        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('dragover');
            }, false);
        });

        zone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            const input = zone.querySelector('input[type="file"]');
            if (input && files.length > 0) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    // Auto-dismiss success flash alert after 5 seconds
    const flash = document.querySelector('.flash-alert.success');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';
            setTimeout(() => flash.remove(), 400);
        }, 5000);
    }

    // Initialize interactive sorting on table headings
    initSortableTables();
});

/**
 * Universal interactive table column sorting on table headings
 */
function initSortableTables() {
    document.querySelectorAll('.admin-table').forEach(table => {
        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        if (!thead || !tbody) return;

        const headers = thead.querySelectorAll('th.sortable');
        if (headers.length === 0) return;

        headers.forEach(th => {
            th.addEventListener('click', (e) => {
                // Ignore if clicked on an input or link inside header
                if (e.target.closest('a, button, input, select')) return;

                const sortKey = th.dataset.sort;
                const sortType = th.dataset.sortType || 'text';
                const colIndex = Array.from(th.parentNode.children).indexOf(th);
                const currentDir = th.classList.contains('sort-asc') ? 'asc' : (th.classList.contains('sort-desc') ? 'desc' : '');
                const newDir = currentDir === 'asc' ? 'desc' : 'asc';

                executeTableSort(table, tbody, headers, th, colIndex, sortKey, newDir, sortType);
            });
        });
    });
}

/**
 * Executes sorting and updates table UI
 */
function executeTableSort(table, tbody, headers, activeTh, colIndex, sortKey, dir, type) {
    const rows = Array.from(tbody.querySelectorAll('tr'));
    // If empty state row, skip
    if (rows.length <= 1 && rows[0]?.querySelector('td[colspan]')) return;

    rows.sort((rowA, rowB) => {
        const cellA = rowA.children[colIndex];
        const cellB = rowB.children[colIndex];
        if (!cellA || !cellB) return 0;

        let valA = cellA.dataset.sortVal !== undefined ? cellA.dataset.sortVal : cellA.innerText.trim();
        let valB = cellB.dataset.sortVal !== undefined ? cellB.dataset.sortVal : cellB.innerText.trim();

        if (type === 'number') {
            valA = parseFloat(String(valA).replace(/[^\d.-]/g, '')) || 0;
            valB = parseFloat(String(valB).replace(/[^\d.-]/g, '')) || 0;
            return dir === 'asc' ? valA - valB : valB - valA;
        } else {
            valA = String(valA).toLowerCase();
            valB = String(valB).toLowerCase();
            const cmp = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
            return dir === 'asc' ? cmp : -cmp;
        }
    });

    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));

    // Update headers styles and icons
    headers.forEach(h => {
        h.classList.remove('sort-asc', 'sort-desc');
        const icon = h.querySelector('.th-sort-icon i');
        if (icon) {
            icon.className = 'fa-solid fa-sort';
        }
    });

    activeTh.classList.add(dir === 'asc' ? 'sort-asc' : 'sort-desc');
    const activeIcon = activeTh.querySelector('.th-sort-icon i');
    if (activeIcon) {
        activeIcon.className = dir === 'asc' ? 'fa-solid fa-arrow-up-wide-short' : 'fa-solid fa-arrow-down-wide-short';
    }

    // Sync URL query without page reload
    if (window.history && window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('sort', sortKey);
        url.searchParams.set('dir', dir);
        window.history.replaceState({}, '', url);
    }

    // Sync select dropdown if present
    const card = table.closest('.admin-card');
    if (card) {
        const select = card.querySelector('.table-sort-select');
        if (select) {
            select.value = `${sortKey}-${dir}`;
        }
    }
}

/**
 * Triggers sort when user selects an option in the table header dropdown
 */
function triggerHeadingSort(val, selectEl) {
    if (!val) return;
    const parts = val.split('-');
    const dir = parts.pop();
    const sortKey = parts.join('-');

    const card = selectEl.closest('.admin-card');
    if (!card) return;

    const table = card.querySelector('.admin-table');
    if (!table) return;

    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    const headers = thead ? thead.querySelectorAll('th.sortable') : [];

    const targetTh = Array.from(headers).find(th => th.dataset.sort === sortKey);
    if (targetTh) {
        const colIndex = Array.from(targetTh.parentNode.children).indexOf(targetTh);
        const sortType = targetTh.dataset.sortType || 'text';
        executeTableSort(table, tbody, headers, targetTh, colIndex, sortKey, dir, sortType);
    }
}
