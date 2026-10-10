import '../css/app.css';
import Primer from 'bigpipe-util/dist/Primer';
import AsyncRequest from 'bigpipe-util/dist/async/AsyncRequest';
import { registerModules, setModuleLoader } from 'bigpipe-util/dist/ModuleRegistry';
import Arbiter from 'bigpipe-util/dist/core/Arbiter';
import Toastr from './Toastr';

Primer();

// The server calls modules by name, e.g. $response->call('tutorial/Image', 'set'). Eager, because
// they are called synchronously. The entrypoint itself is left out.
const modules = import.meta.glob(['./**/*.{js,jsx}', '!./app.js'], { eager: true });

setModuleLoader(name => (modules[`./${name}.js`] ?? modules[`./${name}.jsx`])?.default);

// The scripts the Bootloader loads are not in the bundle: they register their modules with this.
window.registerModules = registerModules;

// The errors of links and forms (rel="async"): show the message the server gave.
AsyncRequest.setDefaultErrorHandler((xhr, error) => {
    new Toastr().error(error.description || error.summary, error.description ? error.summary : undefined);
});

const arbiter = new Arbiter();
let timer;

arbiter.subscribe('ajaxpipe/send', () => {
    const bar = document.getElementById('progress');
    clearTimeout(timer);
    bar.classList.add('on');
    bar.style.width = '30%';
    timer = setTimeout(() => (bar.style.width = '70%'), 400);
});

arbiter.subscribe('quickling/response', () => {
    const bar = document.getElementById('progress');
    clearTimeout(timer);
    bar.style.width = '100%';
    setTimeout(() => {
        bar.classList.remove('on');
        bar.style.width = '0';
    }, 300);
});

function markActiveLink(path) {
    document.querySelectorAll('.topbar a[data-match]').forEach(link => {
        link.classList.toggle('active', new RegExp(link.dataset.match).test(path));
    });
}

arbiter.subscribe('ajaxpipe/first_response', ({ uri }) => markActiveLink(new URL(uri, location.href).pathname));

// A link to a part of another page, e.g. /#tutorials: scroll to it once that page is shown.
arbiter.subscribe('quickling/response', ({ response }) => {
    if (response.is_last && location.hash) {
        setTimeout(() => document.getElementById(decodeURIComponent(location.hash.slice(1)))?.scrollIntoView());
    }
});

// The switch between light and dark. The <head> sets the theme before the first paint; a choice made here is
// remembered, and without one the page follows the system.
function setTheme(theme) {
    const root = document.documentElement;

    root.classList.add('switching-theme');
    root.dataset.theme = theme;
    setTimeout(() => root.classList.remove('switching-theme'), 50);

    const button = document.getElementById('theme-switch');
    if (button) {
        const next = theme === 'dark' ? 'light' : 'dark';
        button.setAttribute('aria-label', `Switch to the ${next} theme`);
        button.title = next === 'dark' ? 'Dark theme' : 'Light theme';
    }
}

function storedTheme() {
    try {
        return localStorage.getItem('theme');
    } catch (e) {
        return null;
    }
}

setTheme(document.documentElement.dataset.theme || 'light');

document.addEventListener('click', event => {
    if (!event.target.closest('#theme-switch')) {
        return;
    }

    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    setTheme(theme);

    try {
        localStorage.setItem('theme', theme);
    } catch (e) {
        // Private mode: the choice lasts until the page is closed.
    }
});

matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
    if (!storedTheme()) {
        setTheme(event.matches ? 'dark' : 'light');
    }
});
