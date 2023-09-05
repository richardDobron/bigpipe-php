---
id: example_forms
title: Calling Forms Programmatically
sidebar_label: Forms
---

In certain scenarios, you may encounter the need to programmatically submit a form, perhaps triggered by user interactions or specific events. One common use case is submitting a form when a user enters data, such as pressing a key or making a selection. To achieve this, you can utilize the `dispatchEvent` method to programmatically dispatch a `submit` event on the form element. This method allows you to automate the form submission process, providing enhanced interactivity and user experience.

```html
<form action="/submit.php"
      method="POST"
      rel="async">
    <input name="username" id="username">
</form>
```

```javascript
const control = document.getElementById('username');

control.addEventListener('input', function () {
    // Programmatically dispatch a submit event on the async form
    this.form.dispatchEvent(new Event('submit', {
        bubbles: true,
        cancelable: true,
    }));
});
```
