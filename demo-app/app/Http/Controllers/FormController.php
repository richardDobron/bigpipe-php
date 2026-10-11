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
            // The message of every field next to it, and an empty one for a valid field.
            foreach (['full_name', 'email'] as $field) {
                $response->setContent('#error-'.$field, e($e->validator->errors()->first($field)));
            }

            // An error with the status 422, not a success: the browser applies the DOM operations above anyway,
            // the FormMonitor of the form keeps the changes as unsaved, and the error handler of the page (app.js)
            // gets the error with its status.
            return $response
                ->setError('Check the form', $e->validator->errors()->first())
                ->send(422);
        }

        return $response
            ->replace('#registration', view('partials.form-success')->render())
            ->send();
    }
}
