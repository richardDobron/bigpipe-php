import AsyncRequest from 'bigpipe-util/dist/async/AsyncRequest';

function debounce(callback, wait) {
    let timer;

    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), wait);
    };
}

// Sends the form with every change, and the server renders it again. The listeners are on the box, not on
// the form, which a replace would throw away.
export default class LiveForm {
    init(box, endpoint) {
        this.box = box;
        this.endpoint = endpoint;
        this.status = document.getElementById('focus-status');

        const send = debounce(() => this.send(), 180);

        box.addEventListener('input', event => event.target.form && send());
        box.addEventListener('change', event => {
            // The switch between morph and replace is not a part of the form the server renders.
            if (event.target.name === 'update_with') {
                this.setMode(event.target.value);
                this.box.querySelectorAll('.mode-switch label').forEach(label => label.classList.toggle('on', label.contains(event.target)));
            } else if (event.target.form) {
                send();
            }
        });
    }

    setMode(mode) {
        this.box.querySelector('input[name=mode]').value = mode;
    }

    send() {
        const form = this.box.querySelector('form');
        const focused = document.activeElement?.name;

        if (this.request) {
            this.request.abort();
        }

        this.request = new AsyncRequest(this.endpoint)
            .setMethod('POST')
            .setData(Object.fromEntries(new FormData(form)))
            // After the DOM operations of the response are applied.
            .setHandler(() => setTimeout(() => this.report(focused)))
            .send();
    }

    // Tells whether the field the user was typing in still has the focus after the response.
    report(focused) {
        if (!this.status || !focused) {
            return;
        }

        const kept = document.activeElement?.name === focused && this.box.contains(document.activeElement);

        this.status.dataset.state = kept ? 'kept' : 'lost';
        this.status.textContent = kept
            ? `The focus stayed in “${focused}”, with the caret where it was.`
            : `The “${focused}” field was replaced: the focus is lost.`;
    }
}
