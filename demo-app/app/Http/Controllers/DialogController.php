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

    /**
     * A dialog with one of its behaviors changed, see the setters of DialogResponse.
     */
    public function behaviorDialog(string $behavior)
    {
        $close = '<button type="button" data-dismiss="modal" class="btn ghost sm">Close</button>';

        $response = (new DialogResponse())->setController('tutorial/ModalLogger');

        match ($behavior) {
            'static' => $response
                ->setTitle('Static backdrop')
                ->setBody('<p>A click on the backdrop or <kbd>Esc</kbd> doesn\'t close this dialog, only the button does.</p>')
                ->setBackdrop('static')
                ->setKeyboard(false),
            'hide-on-success' => $response
                ->setTitle('Hide on success')
                ->setBody(sprintf(
                    '<p>Save sends a request from inside of the dialog: when it succeeds, the dialog closes itself.</p>'
                    .'<p style="margin-top:16px"><a href="%s" ajaxify="%s" rel="async-post" class="btn sm">Save</a></p>',
                    url('/tutorial/dialogs'),
                    route('dialog.behavior-save'),
                ))
                ->setHideOnSuccess(),
            'centered' => $response
                ->setTitle('Animated and centered')
                ->setBody('<p>The dialog fades in and out, in the middle of the window instead of a third from its top.</p>')
                ->setAnimate(true)
                ->setPosition(centered: true),
            'delayed' => $response
                ->setTitle('Delayed')
                ->setBody('<p>The response came right away, and the dialog was shown a second later.</p>')
                ->setTimeout(1000),
            'free-focus' => $response
                ->setTitle('Free focus')
                ->setBody('<p>The focus stays on the page: <kbd>Tab</kbd> goes past the dialog, and when it closes the focus doesn\'t return to the button.</p>'
                    .'<p style="margin-top:16px"><input type="text" placeholder="Not focused when the dialog opens" autocomplete="off"></p>')
                ->setAutoFocus(false)
                ->setTrapFocus(false)
                ->setRefocus(false),
            default => abort(404),
        };

        return $response
            ->setFooter($close)
            ->dialog()
            ->send();
    }

    public function behaviorSave()
    {
        return (new AsyncResponse())
            ->call('Toastr', 'success', ['Saved: the request succeeded, and the dialog closed itself.'])
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
