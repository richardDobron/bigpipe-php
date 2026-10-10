// Toasts the server shows with $response->call('Toastr', 'success', ['Saved.']). The stack lives outside the canvas,
// so a toast stays on the screen across a page transition.
const DURATION = 4500;
const LIMIT = 4;

const TYPES = {
    success: { title: 'Done', role: 'status', icon: '<path d="m4.5 8.5 2.5 2.5 4.5-5.5"/>' },
    error: { title: 'Something went wrong', role: 'alert', icon: '<path d="M8 4.75v4M8 11.25v.01"/>' },
    info: { title: 'Note', role: 'status', icon: '<path d="M8 7.25v4M8 4.75v.01"/>' },
};

let stack;

function getStack() {
    if (!stack || !stack.isConnected) {
        stack = document.createElement('div');
        stack.className = 'toasts';
        document.body.appendChild(stack);
    }

    return stack;
}

function dismiss(toast) {
    if (toast.classList.contains('is-leaving')) {
        return;
    }

    clearTimeout(toast.timer);
    toast.classList.add('is-leaving');
    toast.addEventListener('animationend', () => toast.remove(), { once: true });
    // Without animations (reduced motion), animationend never fires.
    setTimeout(() => toast.remove(), 400);
}

function show(type, message, title) {
    const { title: defaultTitle, role, icon } = TYPES[type];
    const toast = document.createElement('div');

    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', role);
    toast.innerHTML = `
        <span class="toast-icon"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icon}</svg></span>
        <div class="toast-body"><strong></strong><p></p></div>
        <button type="button" class="toast-close" aria-label="Close">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="m4.5 4.5 7 7m0-7-7 7"/></svg>
        </button>
        <span class="toast-time"></span>`;
    // The texts come from the server: as text, never as HTML.
    toast.querySelector('strong').textContent = title || defaultTitle;
    toast.querySelector('p').textContent = message;
    toast.querySelector('.toast-close').addEventListener('click', () => dismiss(toast));

    // Hovered or focused, the toast waits; the bar shows the time left.
    let remaining = DURATION;
    let started;
    let running = false;
    const bar = toast.querySelector('.toast-time');
    const run = () => {
        if (running || toast.matches(':hover, :focus-within')) {
            return;
        }

        running = true;
        started = performance.now();
        bar.style.animationPlayState = 'running';
        toast.timer = setTimeout(() => dismiss(toast), remaining);
    };
    const pause = () => {
        if (!running) {
            return;
        }

        running = false;
        clearTimeout(toast.timer);
        remaining -= performance.now() - started;
        bar.style.animationPlayState = 'paused';
    };

    bar.style.animationDuration = `${DURATION}ms`;
    toast.addEventListener('mouseenter', pause);
    toast.addEventListener('mouseleave', run);
    toast.addEventListener('focusin', pause);
    toast.addEventListener('focusout', run);

    const container = getStack();
    container.prepend(toast);
    [...container.children].slice(LIMIT).forEach(dismiss);
    run();

    return toast;
}

export default class Toastr {
    success(message, title) {
        return show('success', message, title);
    }

    error(message, title) {
        return show('error', message, title);
    }

    info(message, title) {
        return show('info', message, title);
    }
}
