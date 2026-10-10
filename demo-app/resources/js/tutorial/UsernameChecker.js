import AsyncRequest from "bigpipe-util/dist/async/AsyncRequest";
import DOM from "bigpipe-util/dist/core/DOM";

function debounce(callback, wait) {
    let timer;

    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), wait);
    };
}

export default class UsernameChecker {
    init(endpoint) {
        this.endpoint = endpoint;

        this.message = document.querySelector('.status-message');
        this.username = document.getElementsByName('username')[0];
        // The field shows the state: checking (a spinner), available or unavailable.
        this.field = this.username.closest('.input-wrap');

        this._bindEvents();
    }

    _bindEvents() {
        const check = debounce(this._checkValidity.bind(this), 250);

        this.username.addEventListener('input', () => {
            this._setState(this.username.value.trim() ? 'typing' : '');
            check();
        });
    }

    _setState(state, message = '') {
        this.field.dataset.state = state;
        DOM.setContent(this.message, message);
    }

    _checkValidity() {
        const username = this.username.value.trim();

        if (this.request) {
            this.request.abort();
        }

        if (!username) {
            this._setState('');

            return;
        }

        this.request = (new AsyncRequest(this.endpoint))
            .setData({
                username,
            })
            .setInitialHandler(() => this._setState('checking', 'Checking availability…'))
            .setHandler(this._showStatus.bind(this))
            .send();
    }

    _showStatus({payload}) {
        this._setState(payload.status, payload.message.__html);
    }
}
