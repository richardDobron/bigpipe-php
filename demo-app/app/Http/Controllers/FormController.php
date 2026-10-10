<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FormController extends Controller
{
    public function registration(Request $request)
    {
        $response = new AsyncResponse();

        try {
            $request->validate([
                'full_name' => 'required|max:100',
                'email' => 'required|email',
            ]);
        } catch (ValidationException $e) {
            // An error, not a success: the FormMonitor of the form keeps the changes as unsaved, and the error
            // handler of the page (app.js) shows the message.
            return $response
                ->setError('Check the form', $e->validator->errors()->first())
                ->send();
        }

        return $response
            ->replace('#registration', view('partials.form-success')->render())
            ->send();
    }
}
