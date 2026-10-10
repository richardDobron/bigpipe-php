// The code of a page is highlighted by Prism when the page loads; after a page transition it is new.
export default class Code {
    highlight() {
        window.Prism?.highlightAll();
    }
}
