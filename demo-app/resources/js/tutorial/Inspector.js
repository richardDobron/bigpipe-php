// Shows what the server sent for each request of a tutorial: the DOM operations, the modules it called
// or defined and the payload, next to the example. It reads the responses of XMLHttpRequest, which
// AsyncRequest uses, and only those behind the for (;;); shield of BigPipe.
const SHIELD = 'for (;;);';
const LIMIT = 8;

function escape(text) {
    return String(text).replace(/[&<>"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char]);
}

function preview(value, length = 64) {
    const text = typeof value === 'string' ? value : JSON.stringify(value);

    return text.length > length ? text.slice(0, length - 1) + '…' : text;
}

function htmlPreview(content) {
    const html = content && typeof content === 'object' ? content.__html : content;

    if (html == null) {
        return '';
    }

    const text = String(html).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

    return text ? `"${preview(text, 40)}"` : '<html>';
}

function lines(data, transition = false) {
    const rows = [];

    if (transition && data.payload?.title) {
        rows.push(['page', 'A page transition', data.payload.title, '']);
    }

    if (data.csrf_refresh) {
        rows.push(['retry', 'The CSRF token was rejected', '', 'the new token is defined, the request is sent again']);
    }

    // The Bootloader: the files a call needs, which the browser loads first unless it has them already.
    Object.entries(data.bootloadable || {}).forEach(([module, entry]) => {
        rows.push(['bootload', module, '', (Array.isArray(entry) ? entry : entry.resources || []).join(', ')]);
    });
    Object.entries(data.resource_map || {}).forEach(([name, { src }]) => {
        rows.push(['resource', name, '', src.replace(location.origin, '')]);
    });

    if (data.error) {
        rows.push(['error', data.errorSummary || `Error ${data.error}`, '', data.errorDescription || '']);
    }

    (data.pagelets || []).forEach(({ id, jsmods }) => {
        rows.push(['pagelet', id || 'a pagelet', '', '']);
        // The modules of a pagelet run when it is shown.
        (jsmods?.require || []).forEach(([module, method, args]) => {
            const name = module.replace(/^bigpipe-util\/dist\//, '');
            rows.push(['call', method ? `${name}.${method}()` : `new ${name}()`, `in ${id}`, args?.length ? preview(args, 48) : '']);
        });
    });

    (data.domops || []).forEach(([operation, selector, , content]) => {
        rows.push(['dom', operation, selector || (transition ? 'the canvas' : 'the clicked element'), htmlPreview(content)]);
    });

    (data.jsmods?.define || []).forEach(([name]) => rows.push(['define', name, '', '']));

    (data.jsmods?.require || []).forEach(([module, method, args]) => {
        const name = module.replace(/^bigpipe-util\/dist\//, '');
        rows.push(['call', method ? `${name}.${method}()` : name, '', args?.length ? preview(args, 48) : '']);
    });

    if (data.payload != null && !(Array.isArray(data.payload) && !data.payload.length)) {
        rows.push(['payload', preview(data.payload, 56), '', '']);
    }

    return rows;
}

export default class Inspector {
    init() {
        // Once for the window: the responses go to the panel of the page shown, see add().
        if (XMLHttpRequest.prototype.__inspected) {
            return;
        }

        const inspector = this;
        const { open, send } = XMLHttpRequest.prototype;

        XMLHttpRequest.prototype.__inspected = true;
        XMLHttpRequest.prototype.open = function (method, url, ...rest) {
            this.__request = { method: String(method).toUpperCase(), url: new URL(url, location.href) };

            return open.call(this, method, url, ...rest);
        };
        XMLHttpRequest.prototype.send = function (...args) {
            const request = this.__request;

            if (request) {
                const start = performance.now();

                this.addEventListener('loadend', () => {
                    const text = this.responseType === '' || this.responseType === 'text' ? this.responseText : '';

                    if (text && text.startsWith(SHIELD)) {
                        try {
                            inspector.add(request, this.status, Math.round(performance.now() - start), JSON.parse(text.slice(SHIELD.length)));
                        } catch (error) {
                            // Not a response of BigPipe after all.
                        }
                    }
                });
            }

            return send.apply(this, args);
        };
    }

    add({ method, url }, status, time, data) {
        // After a page transition the panel is another one, or there is none.
        this.log = document.getElementById('inspector-log');

        if (!this.log) {
            return;
        }

        const rejected = status >= 400 || Boolean(data.error || data.csrf_refresh);
        const operations = lines(data, [...url.searchParams.keys()].some(name => name.startsWith('quickling')));
        // Responses that do the same (a poller, a field checked on each keystroke) are folded into one;
        // the values of a payload may differ, the operations not.
        const key = [method, url.pathname, status, ...operations.map(([kind, name, target]) => kind === 'payload' ? kind : kind + name + target)].join(' ');
        const rows = operations.map(([kind, name, target, detail]) => `
            <li class="op op-${kind}">
                <span class="kind">${escape(kind)}</span>
                <span class="name">${escape(name)}${target ? ` <em>${escape(target)}</em>` : ''}</span>
                ${detail ? `<span class="detail">${escape(detail)}</span>` : ''}
            </li>`).join('') || '<li class="op"><span class="name">An empty response</span></li>';

        this.log.parentElement.classList.add('has-entries');

        let entry = this.log.firstElementChild;

        if (entry && entry.dataset.key === key) {
            entry.dataset.count = String(Number(entry.dataset.count) + 1);
            entry.classList.remove('flash');
            void entry.offsetWidth;
        } else {
            entry = document.createElement('li');
            entry.dataset.key = key;
            entry.dataset.count = '1';
            this.log.prepend(entry);
        }

        const count = Number(entry.dataset.count);
        const open = entry.querySelector('details')?.open ? ' open' : '';

        entry.className = 'entry flash' + (rejected ? ' failed' : '');
        entry.innerHTML = `
            <div class="request">
                <span class="method">${escape(method)}</span>
                <span class="path">${escape(url.pathname + url.search)}</span>
                <span class="count">${count > 1 ? '×' + count : ''}</span>
                <span class="meta">${status} · ${time} ms</span>
            </div>
            <ul class="ops">${rows}</ul>
            <details${open}><summary>Raw response</summary><pre>${escape(JSON.stringify(data, null, 2))}</pre></details>`;

        while (this.log.children.length > LIMIT) {
            this.log.lastElementChild.remove();
        }
    }
}
