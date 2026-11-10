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
});

