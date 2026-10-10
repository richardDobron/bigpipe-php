import React from 'react';
import { createRoot } from 'react-dom/client';

export default class ReactRenderer {
    createComponent(element, container) {
        createRoot(container).render(element);
    }

    constructAndRenderComponent(component, props, container) {
        this.createComponent(React.createElement(component, props), container);
    }
}
