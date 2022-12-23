<?php

namespace App\Http\Controllers;

use dobron\BigPipe\DialogResponse;

class DialogController extends Controller
{
    private $response;

    public function __construct(DialogResponse $response)
    {
        $this->response = $response;
    }

    public function modelDialog()
    {
        $this->response
            ->setController("require('tutorial/ModalLogger')")
            ->setTitle('Dialog example')
            ->setBody('Content . . .')
            ->setFooter(<<<HTML
<button type="button" data-dismiss="modal" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
    Cancel
</button>
HTML)
            ->dialog();

        return $this->response->send();
    }

    public function htmlDialog()
    {
        $this->response
            ->setController("require('tutorial/ModalLogger')")
            ->setDialog(view('tutorial.html-dialog')->render())
            ->dialog();

        return $this->response->send();
    }

    public function reactDialog()
    {
        $this->response
            ->setController("require('tutorial/ModalRenderer')", [[
                'component' => $this->response->transport()->transportModule('tutorial/ReactModal'),
                'props' => [
                    'full_name' => 'Margot Foster',
                    'job_title' => 'Backend Developer',
                    'email_address' => 'margotfoster@example.com',
                    'expected_salary' => '$120,000',
                ],
            ]])
            ->dialog();

        return $this->response->send();
    }

    public function commonDialog()
    {
        $this->response
            ->setController("require('tutorial/ModalLogger')")
            ->setDialog(view('tutorial.form-dialog')->render())
            ->dialog();

        return $this->response->send();
    }

    public function deleteDialog()
    {
        $this->response
            ->setController("require('tutorial/ModalLogger')")
            ->setDialog(view('tutorial.delete-dialog')->render())
            ->dialog();

        return $this->response->send();
    }

    public function confirmDialog()
    {
        $this->response
            ->closeDialogs()
            ->setController("require('tutorial/ModalLogger')")
            ->setDialog(view('tutorial.confirm-dialog')->render())
            ->dialog();

        return $this->response->send();
    }

    public function closeDialogs()
    {
        $this->response->closeDialogs();

        $this->response->bigPipe()->require("require('Toastr').success()", [
            'All dialogs has been closed...',
        ]);


        return $this->response->send();
    }
}
