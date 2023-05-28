---
id: react_integration
title: React Integration
sidebar_label: React Integration
---

To integrate BigPipe with React, follow these steps:

1. To integrate BigPipe with React, follow these steps:
```shell
$ npm install react react-dom @babel/preset-react
# or
$ yarn add react react-dom @babel/preset-react
```

add `@babel/preset-react` to your Babel configuration

2. Create a file named `ModalRenderer.js` that renders dialogs using React:

```javascript
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
```

3. Create a file named `ReactRenderer.js` to create React components:

```javascript
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
```

## Example

Here's an example of how to integrate BigPipe with React in your PHP code:
```php
$response = new \dobron\BigPipe\DialogResponse();

$response
    ->setController("require('ModalRenderer')", [
        [
            'component' => \dobron\BigPipe\TransportMarker::transportModule('ConfirmationModal'),
            'props' => [
                'message' => 'Are you sure you want to continue?',
                'submitURI' => '/delete.php',
            ],
            'modalSize' => 'modal-md',
        ],
    ])
    ->dialog([
        'backdrop' => 'static',
    ]);

$response->send();
```

And here's an example of a React component named `ConfirmationModal`:
```tsx
import React from "react";

type Props = {
    dialog: {};
    message: string;
    handleCancel?: () => void;
    submitURI?: string;
    submitHandler?: () => void;
};

export default class ConfirmationModal extends React.Component<Props> {
    handleSubmit() {
        const { submitHandler, dialog } = this.props;

        submitHandler(this.props);

        dialog.hide();
    }

    handleCancel() {
        const { handleCancel, dialog } = this.props;

        if (handleCancel) {
            handleCancel();
        }

        dialog.hide();
    }

    render() {
        const { message } = this.props;

        return (
            <>
                <div className="modal-header">
                    <strong>
                        Confirmation required
                    </strong>
                    <button type="button" className="close" data-dismiss="modal">×</button>
                </div>
                <div className="modal-body" dangerouslySetInnerHTML={{__html: message}}/>
                <div className="modal-footer">
                    <button className="btn" onClick={() => this.handleCancel()}>
                        Cancel
                    </button>
                    <button className="btn btn-primary" onClick={() => this.handleSubmit()}>
                        Continue
                    </button>
                </div>
            </>
        );
    }
}

ConfirmationModal.defaultProps = {
    submitHandler: function (props) {
        const { submitURI } = props;

        if (submitURI) {
            window.location = submitURI;
        }
    },
}
```
