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

## Live Example
To see this API in action, you can refer to the provided [demo page](http://bigpipe.xf.cz/tutorial/redirecting).

