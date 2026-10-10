// A module the page does not bundle: the Bootloader loads this file (and editor.css) the first time the server
// calls the "Editor" module, then calls it. The file registers the module when it runs.
(function () {
    window.editorScriptRuns = (window.editorScriptRuns || 0) + 1;

    var loadedAt = Math.round(performance.now());

    function words(text) {
        return (text.trim().match(/\S+/g) || []).length;
    }

    function wrap(textarea, before, after) {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var value = textarea.value;
        var selected = value.slice(start, end) || 'text';

        textarea.value = value.slice(0, start) + before + selected + after + value.slice(end);
        textarea.focus();
        textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function Editor() {}

    Editor.prototype.open = function (textarea) {
        if (textarea.closest('.editor')) {
            textarea.focus();
            return;
        }

        var editor = document.createElement('div');
        editor.className = 'editor';
        editor.innerHTML =
            '<div class="editor-bar">' +
                '<button type="button" data-wrap="**" title="Bold"><b>B</b></button>' +
                '<button type="button" data-wrap="_" title="Italic"><i>I</i></button>' +
                '<button type="button" data-wrap="`" title="Code">&lt;/&gt;</button>' +
                '<span class="editor-count"></span>' +
            '</div>' +
            '<p class="editor-note">editor.js ran ' + window.editorScriptRuns + '× · loaded at ' + loadedAt + ' ms</p>';

        textarea.parentNode.insertBefore(editor, textarea);
        editor.insertBefore(textarea, editor.lastChild);

        var count = editor.querySelector('.editor-count');
        var update = function () {
            var n = words(textarea.value);
            count.textContent = n + (n === 1 ? ' word' : ' words') + ' · ' + Math.max(1, Math.round(n / 200)) + ' min read';
        };

        editor.querySelector('.editor-bar').addEventListener('click', function (event) {
            var button = event.target.closest('button[data-wrap]');

            if (button) {
                wrap(textarea, button.dataset.wrap, button.dataset.wrap);
            }
        });
        textarea.addEventListener('input', update);
        update();
        textarea.focus();
    };

    window.registerModules({ Editor: Editor });
})();
