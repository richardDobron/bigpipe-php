import ReactRenderer from "./ReactRenderer";

export default class ModalRenderer {
    constructor(dialog, {component, props = {}}) {
        const container = dialog.el.querySelector('.modal-content');

        (new ReactRenderer).constructAndRenderComponent(
            component,
            props,
            container
        );
    }
}
