<?php

namespace App\Http\Controllers;

use dobron\BigPipe\AsyncResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FormController extends Controller
{
    private $response;

    public function __construct(AsyncResponse $response)
    {
        $this->response = $response;
    }

    public function registration(Request $request)
    {
        try {
            $request->validate([
                'full_name' => 'required|max:100',
                'email' => 'required|email',
            ]);
        } catch (ValidationException $e) {
            $this->response->bigPipe()->require("require('Toastr').error()", [
                $e->validator->errors()->first(),
            ]);

            return response()->json($this->response->getResponse());
        }

        $this->response->replace('form', view('partials.form-success'));

        return response()->json($this->response->getResponse());
    }
}
