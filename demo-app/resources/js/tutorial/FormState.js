import FormMonitor from 'bigpipe-util/dist/core/FormMonitor';

// Watches a form with FormMonitor: leaving the page (a link, a page transition, closing the tab) with unsaved changes
// asks first, and a successful submit makes the form clean. The status shows what the monitor knows.
export default class FormState {
    watch(form, status, message) {
        const monitor = new FormMonitor(form, { message });
        const show = dirty => {
            status.dataset.state = dirty ? 'dirty' : 'clean';
            status.textContent = dirty ? 'Unsaved changes: leaving the page asks first' : 'No unsaved changes';
        };

        monitor.subscribe('dirty', () => show(true));
        monitor.subscribe('clean', () => show(false));
        show(false);
    }
}
