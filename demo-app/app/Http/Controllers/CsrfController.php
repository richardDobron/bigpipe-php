<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use Illuminate\Http\Request;

class CsrfController extends Controller
{
    /**
     * The token of the session is replaced, e.g. the session expired: the browser still has the old one.
     */
    public function expire(Request $request)
    {
        $request->session()->regenerateToken();

        return (new AsyncResponse())
            ->setContent('#csrf-status', 'The token of the session was replaced. The browser still has the old one.')
            ->send();
    }

    /**
     * The request carries the old token: the handler in bootstrap/app.php answers the 419, the browser gets
     * the new token and sends this request again.
     */
    public function save()
    {
        return (new AsyncResponse())
            ->setContent('#csrf-status', 'Saved. The request was sent again with a new token ('.now()->format('H:i:s').').')
            ->call('Toastr', 'success', ['Saved after the token was refreshed.'])
            ->send();
    }
}
