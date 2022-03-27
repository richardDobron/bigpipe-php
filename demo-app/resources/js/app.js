window.require = (modulePath) => {
    return modulePath.startsWith('bigpipe-util/')
        ? require('bigpipe-util/' + modulePath.substring(13) + '.js').default
        : require('./' + modulePath).default;
};

// laravel csrf token
const csrf = document.querySelector("[name=csrf-token]");

XMLHttpRequest.prototype._open = XMLHttpRequest.prototype.open;
XMLHttpRequest.prototype.open = function () {
    this._open.apply(this, arguments);
    this.setRequestHeader('X-CSRF-TOKEN', csrf.getAttribute('content'));
};
