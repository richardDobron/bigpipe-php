import Arbiter from 'bigpipe-util/dist/core/Arbiter';

// The pagelets tutorial: when the browser showed each pagelet, in milliseconds since the page started
// loading, or since the page transition that brought it was sent.
let start = 0;

new Arbiter().subscribe('ajaxpipe/send', () => {
    start = performance.now();
});

const since = () => `${Math.round(performance.now() - start)} ms`;

export default class Arrival {
    mark(id) {
        const card = document.querySelector(`#pagelet_${id} .pl-card`);

        if (card) {
            card.querySelector('[data-shown]').textContent = since();
            card.classList.add('is-shown');
        }
    }

    // The page script runs after the last pagelet. A pagelet that fell back has no modules of its own.
    done() {
        document.querySelectorAll('.pl-card.is-failed [data-shown]').forEach(time => {
            time.textContent = since();
        });

        const flushed = [...document.querySelectorAll('.pl-card[data-flushed]')].map(card => Number(card.dataset.flushed));
        const last = document.getElementById('last-flushed');

        if (last && flushed.length) {
            last.textContent = `${Math.max(...flushed)} ms`;
        }
    }
}
