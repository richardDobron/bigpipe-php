<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        return $this->page('profile.show', [], 'Profile');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:2048']);

        $path = $request->file('avatar')->store('avatars', 'public');
        $request->user()->update(['avatar' => $path]);

        return (new AsyncResponse())
            ->call('Avatar/Swap', null, ['#avatar', Storage::url($path)])
            ->setContent('#error-avatar', '')
            ->call('Toastr', 'success', ['Your avatar was updated.'])
            ->send();
    }
}
