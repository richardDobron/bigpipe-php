---
id: wait_for_load
title: "Wait for load"
sidebar_label: "Wait for load"
---

Page loading sometimes takes more time than expected. This can be due to various reasons, such as network latency, server response time, or resource loading issues. To handle such scenarios gracefully, you can implement a loading mechanism that waits for the page to load completely before executing certain actions.

```javascript
import { byTag } from "bigpipe-util/src/core/Parent";
import onAfterLoad from "bigpipe-util/src/core/onAfterLoad";
import waitForLoad from "bigpipe-util/src/core/waitForLoad";

(function setupClickListener() {
    const clickHandler = function (event) {
        const { target } = event;
        const RELATIONSHIP_REGEX = /async(?:-post)?|dialog/;

        const linkNodeOnClicked = byTag(target, 'A');

        if (!linkNodeOnClicked || ! linkNodeOnClicked.rel?.match(RELATIONSHIP_REGEX)) {
            return;
        }

        waitForLoad(linkNodeOnClicked, event, function (e) {
            e.preventDefault();

            return false;
        });

        event.preventDefault()
    };

    document.documentElement.addEventListener('click', clickHandler);

    onAfterLoad(() => {
        document.documentElement.removeEventListener('click', clickHandler);
    });
})();
```
