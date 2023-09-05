---
id: arbiter
title: "Arbiter: A Simple Event System"
sidebar_label: Event system
---

Arbiter is a straightforward event system designed to facilitate communication between different parts of your application, particularly for inter-module communication. It enables efficient data exchange among modules, contributing to a more organized and modular codebase.

Here's a concise code example demonstrating how to use Arbiter:

```javascript
import Arbiter from 'bigpipe-util/src/core/Arbiter';

// Create an instance of Arbiter
const arbiter1 = new Arbiter();

// Subscribe to an event with a callback function
arbiter1.subscribe("ORDER/READY", (order) => {
    console.warn(order);
});

// To unsubscribe, use one of the following methods:
// 1. Unsubscribe a specific callback function
// arbiter1.unsubscribe("ORDER/READY", callbackFunction);

// 2. Clear all subscribers for a specific event
// arbiter1.clearSubscribers("ORDER/READY");

// Simulate an event trigger after a delay
setTimeout(() => {
    const arbiter2 = new Arbiter();

    // Trigger an event with data
    arbiter2.inform("ORDER/READY", {
        orderId: 12300,
        customer: "John Doe",
        total: 100.99,
    });
}, 1000);
```
