import { requireModule } from 'bigpipe-util/dist/ModuleRegistry';

export default class Highlight {
    pulse() {
        // The element the server defined, found by its id when it is required.
        const element = requireModule('tutorial/Target');

        element.classList.remove('is-highlighted');
        void element.offsetWidth;
        element.classList.add('is-highlighted');
        element.textContent = `<${element.tagName.toLowerCase()} id="${element.id}">`;

        clearTimeout(element.__highlight);
        element.__highlight = setTimeout(() => element.classList.remove('is-highlighted'), 1400);
    }
}
