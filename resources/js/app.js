import * as bootstrap from 'bootstrap';
import { initResidentPicker } from './resident-picker';

window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    // Close the phone slide-out menu before a modal (e.g. Log Out) opens on top of it.
    document.addEventListener('show.bs.modal', () => {
        const sidebar = document.getElementById('appSidebar');

        if (sidebar) {
            bootstrap.Offcanvas.getInstance(sidebar)?.hide();
        }
    });

    // Whole table rows open their record, so phones get a large tap target.
    // The row's own link stays for keyboard and screen-reader users.
    document.addEventListener('click', (event) => {
        const row = event.target.closest('tr[data-href]');

        if (!row || event.target.closest('a, button, input, select, textarea, label')) {
            return;
        }

        window.location.href = row.dataset.href;
    });

    document.querySelectorAll('[data-resident-picker]').forEach(initResidentPicker);

    // Re-open a modal after a failed form submission so validation errors stay visible.
    const modalToReopen = document.body.dataset.reopenModal;

    if (modalToReopen) {
        const element = document.getElementById(modalToReopen);

        if (element) {
            bootstrap.Modal.getOrCreateInstance(element).show();
        }
    }
});
