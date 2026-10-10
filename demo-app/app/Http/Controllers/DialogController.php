<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Arch\BigPipe\DialogResponse;
use dobron\BigPipe\TransportMarker;

class DialogController extends Controller
{
    public function modelDialog()
    {
        return (new DialogResponse())
            ->setController('tutorial/ModalLogger')
            ->setTitle('Dialog example')
            ->setBody('Content . . .')
            ->setFooter(<<<HTML
<button type="button" data-dismiss="modal" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
    Cancel
</button>
HTML)
            ->dialog()
            ->send();
    }

    public function htmlDialog()
    {
        return (new DialogResponse())
            ->setController('tutorial/ModalLogger')
            ->setDialog(view('tutorial.html-dialog')->render())
            ->dialog()
            ->send();
    }

    public function reactDialog()
    {
        return (new DialogResponse())
            ->setController('tutorial/ModalRenderer', [[
                'component' => TransportMarker::module('tutorial/ReactModal'),
                'props' => [
                    'full_name' => 'Margot Foster',
                    'job_title' => 'Backend Developer',
                    'email_address' => 'margotfoster@example.com',
                    'expected_salary' => '$120,000',
                ],
            ]])
            ->dialog()
            ->send();
    }

    public function commonDialog()
    {
        return (new DialogResponse())
            ->setController('tutorial/ModalLogger')
            ->setDialog(view('tutorial.form-dialog')->render())
            ->dialog()
            ->send();
    }

    public function deleteDialog()
    {
        return (new DialogResponse())
            ->setController('tutorial/ModalLogger')
            ->setDialog(view('tutorial.delete-dialog')->render())
            ->dialog()
            ->send();
    }

    public function confirmDialog()
    {
        return (new DialogResponse())
            ->closeDialogs()
            ->setController('tutorial/ModalLogger')
            ->setDialog(view('tutorial.confirm-dialog')->render())
            ->dialog()
            ->send();
    }

    public function closeDialogs()
    {
        return (new DialogResponse())
            ->closeDialogs()
            ->call('Toastr', 'success', ['All dialogs were closed.'])
            ->send();
    }
}
