export default function Swap(selector, url) {
    document.querySelector(selector).src = url + '?t=' + Date.now();
}
