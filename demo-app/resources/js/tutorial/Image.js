export default class Image {
    set(source) {
        const box = document.getElementById('image-box');
        const child1 = box.firstElementChild;
        const child2 = box.lastElementChild;
        const image = child2.querySelector('img');

        child1.classList.add('hidden');
        child2.classList.remove('hidden');

        image.src = source;
    }
}
