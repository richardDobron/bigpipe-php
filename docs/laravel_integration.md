---
id: laravel_integration
title: Laravel Integration
sidebar_label: Laravel Integration
---

To integrate BigPipe into your existing Laravel application, follow the [Installation instructions](getting_started#-installing).

However, we recommend creating your own class that extends the basic class definitions and includes an overridden `send()` method for processing the response:

1. Create the file `app/Arch/BigPipe/AsyncResponse.php`:

    ```php
    <?php
    
    namespace App\Arch\BigPipe;
    
    use Illuminate\Http\Response;
    
    class AsyncResponse extends \dobron\BigPipe\AsyncResponse
    {
        /**
         * Send response
         */
        public function send(int $status = 200): Response
        {
            return response($this->buildResponseString(), $status)
                ->header('Content-Type', 'application/javascript');
        }
    }
    
    ```

2. Create the file `app/Arch/BigPipe/DialogResponse.php`:

    ```php
    <?php
    
    namespace App\Arch\BigPipe;
    
    use Illuminate\Http\Response;
    
    class DialogResponse extends \dobron\BigPipe\DialogResponse
    {
        /**
         * Send response
         */
        public function send(int $status = 200): Response
        {
            return response($this->buildResponseString(), $status)
                ->header('Content-Type', 'application/javascript');
        }
    }
    
    ```

This approach allows you to seamlessly integrate BigPipe into your Laravel application while maintaining a consistent coding structure.

## Example of Class Implementation

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Arch\BigPipe\DialogResponse;

class UserController extends Controller
{
    public function __construct(
        private DialogResponse $response
    ) {
    }

    public function showUserDetailsDialog(User $user)
    {
        $this->response
            ->setContent('User: ' . $user->email)
            ->dialog();

        return $this->response->send();
    }
}

```
