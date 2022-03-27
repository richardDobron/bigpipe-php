import DOM from "bigpipe-util/src/core/DOM";

export default class Collections {
    setup(box, map, set) {
        const child1 = box.firstElementChild;
        const child2 = box.lastElementChild;

        child1.classList.add('hidden');
        child2.classList.remove('hidden');

        let logger = '';

        map.forEach((value, key) => {
            logger += this._highlightConsole(`${key} is ${value} years old!`);
        });

        logger += '<br />';

        logger += this._highlightConsole([...set].join(', '));

        DOM.setContent(child2, logger);
    }

    _highlightConsole(message) {
        console.log(message);

        return `<div>console<span class="token punctuation">.</span><span class="token function">log<span class="token punctuation">(</span></span><span class="token string">'${message}'</span><span class="token punctuation">)</span></div>`;
    }
}
