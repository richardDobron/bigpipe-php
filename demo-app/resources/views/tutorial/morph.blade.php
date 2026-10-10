@extends('layout')

@section('header', 'Morph')

@section('description')
    Render the same template again on the server and send it: morph updates only what changed and keeps the rest of the elements,
    so the field the user is typing in keeps its focus, its caret and its value. It uses the morph algorithm of Alpine and Livewire.
@endsection

@section('bullet-description')
    <ul role="list">
        <li><div><h3>morph($selector, $html)</h3><p>The element and its children become the new HTML, element by element.</p></div></li>
        <li><div><h3>morphContent($selector, $html)</h3><p>Only the children: the element and its attributes stay.</p></div></li>
        <li><div><h3>key or id</h3><p>Keeps list items that are reordered.</p></div></li>
    </ul>
@endsection

@section('code', <<<'CODE'
public function quote(Request $request)
{
    $html = view('quote', $this->quote($request))->render();

    return (new AsyncResponse())
        // keeps the focus and the caret of the field being typed in
        ->morph('#quote', $html)
        // ->replace('#quote', $html) would throw the form away
        ->send();
}
CODE
)

@php(\dobron\BigPipe\BigPipe::page()->call('tutorial/LiveForm', 'init', [
    \dobron\BigPipe\TransportMarker::element('quote-box'),
    route('morph.quote'),
]))

@section('examples')
    <x-example title="A form rendered on the server with every keystroke" caption="Type in Seats or Coupon: the server renders the whole form again, with the total and the errors. Switch to replace and type again to see the difference.">
        <div id="quote-box" class="surface">
            <div class="mode-switch">
                <span class="label">Update with</span>
                <div class="segmented">
                    <label class="on"><input type="radio" name="update_with" value="morph" checked> morph</label>
                    <label><input type="radio" name="update_with" value="replace"> replace</label>
                </div>
            </div>
            @include('tutorial._quote', \App\Http\Controllers\PageletController::quoteData())
            <p id="focus-status" class="focus-status" aria-live="polite">Type in a field.</p>
        </div>
    </x-example>
@endsection
