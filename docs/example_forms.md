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

## File uploads

A form with `rel="async"` and a chosen file is sent as `multipart/form-data`, so the files are in `$_FILES` as usual. The
form reports the progress of the upload while it is sent, see [file uploads in
`bigpipe-util`](https://github.com/richardDobron/bigpipe-util/blob/main/docs/primer.md#file-uploads):

```html
<form action="/upload.php" method="POST" rel="async">
    <input type="file" name="photo">
    <progress></progress>
    <button type="submit">Upload</button>
</form>
```

```php
<?php
$response = new \dobron\BigPipe\AsyncResponse();

if (move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/uploads/' . basename($_FILES['photo']['name']))) {
    $response->setContent('#status', 'Uploaded.');
} else {
    $response->setError('Upload failed', 'The file could not be saved.');
}

$response->send();
```

To warn about unsaved changes before the user leaves a form, see `FormMonitor`, which can be started from PHP.

## Live Example
A form sent in the background, with its validation errors, in the [demo page](http://bigpipe.etexweb.sk/tutorial/forms).
