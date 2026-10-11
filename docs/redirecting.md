---
id: redirecting
title: 🔄 Refresh & Redirecting API
sidebar_label: Refresh & Redirecting
---

The Refresh & Redirecting API allows you to introduce delays (in milliseconds) for either refreshing the current page or redirecting to another URL.

## Methods

### **reload**
- This method reloads the current page after a specified delay.

    ```php
    <?php
    $response = new \dobron\BigPipe\AsyncResponse();

    $response->reload(250); // Reload the page with a delay of 250ms
  
    $response->send();
    ```

### **redirect**
- Use this method to redirect to a specified URL after a specified delay.

    ```php
    <?php
    $response = new \dobron\BigPipe\AsyncResponse();

    $response->redirect('/onboarding', 500); // Redirect with a delay of 500ms
  
    $response->send();
    ```

- The page is loaded in full. `$replace` replaces the current history entry, e.g. of a form that should not be sent
  again with the back button, and `$transition` loads it with a [page transition](page_transitions.md) when the page
  has them on. Keep page transitions off for a redirect that changes the layout, e.g. after a login. A redirect with a
  delay does not happen once the user leaves the page with a page transition.

    ```php
    $response->redirect('/posts/123', replace: true, transition: true);
    ```

## Live Example
To see this API in action, you can refer to the provided [demo page](http://bigpipe.etexweb.sk/tutorial/redirecting).

