<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    private const USERNAME_AVAILABLE = 'available';
    private const USERNAME_UNAVAILABLE = 'unavailable';

    public function checkUsername(Request $request)
    {
        $username = (string) $request->get('username');
        $status = preg_match('/[0-9]+/', $username)
            ? self::USERNAME_AVAILABLE
            : self::USERNAME_UNAVAILABLE;

        $message = $status === self::USERNAME_AVAILABLE
            ? 'Username '.e($username).' is available.'
            : 'Username '.e($username).' is unavailable.';

        usleep(500000);

        return (new AsyncResponse())
            ->setPayload([
                'username' => $username,
                'status' => $status,
                'message' => TransportMarker::html($message),
            ])
            ->send();
    }
}
