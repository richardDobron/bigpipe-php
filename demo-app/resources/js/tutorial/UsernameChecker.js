import AsyncRequest from "bigpipe-util/src/async/AsyncRequest";
import debounce from "fbjs/lib/debounceCore";
import DOM from "bigpipe-util/src/core/DOM";

export default class UsernameChecker {
    init(endpoint) {
        this.endpoint = endpoint;

        this.message = document.querySelector('.status-message');
        this.username = document.getElementsByName('username')[0];

        this._bindEvents();
    }

    _bindEvents() {
        this.username.addEventListener('input', debounce(this._checkValidity.bind(this), 250));
    }

    _checkValidity(event) {
        const { value: username } = this.username;

        if (this.request) {
            this.request.abort();
        }

        this.request = (new AsyncRequest(this.endpoint))
            .setData({
                username,
            })
            .setInitialHandler(() => {
                this.message.innerHTML = 'Checking...';
                this.message.classList.remove('text-red-600', 'text-green-600');
            })
            .setHandler(this._showStatus.bind(this))
            .send();
    }

    _showStatus({payload}) {
        DOM.setContent(this.message, payload.message.__html);
        this.message.classList.add(payload.status === 'available' ? 'text-green-600' : 'text-red-600');
    }
}
