<?php

namespace App\Http\Controllers;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    private const USERNAME_AVAILABLE = 'available';
    private const USERNAME_UNAVAILABLE = 'unavailable';
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function checkUsername(Request $request)
    {
        $username = $request->get('username');
        $status = preg_match('/[0-9]+/', $username)
            ? self::USERNAME_AVAILABLE
            : self::USERNAME_UNAVAILABLE;

        $message = $status === self::USERNAME_AVAILABLE
            ? "Username $username is available."
            : "Username $username is unavailable.";

        $this->response->setPayload([
            'username' => $username,
            'status' => $status,
            'message' => TransportMarker::transportHtml($message)
        ]);

        usleep(500000);

        return response()->json($this->response->getResponse());
    }
}
