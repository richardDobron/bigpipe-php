import React from 'react';
import ReactDOM from 'react-dom';

export default class ReactRenderer {
    createComponent(element, container, callback) {
        ReactDOM.render(element, container, callback);
    }

    constructAndRenderComponent(component, props, container, callback) {
        const element = React.createElement(component, props);
        return this.createComponent(element, container, callback)
    }
}
