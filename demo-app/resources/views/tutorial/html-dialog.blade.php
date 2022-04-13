@extends('partials.dialog')

@section('title', 'Terms of Service')

@section('body')
    <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400">
        With less than a month to go before the European Union enacts new consumer privacy laws for its citizens, companies around the world are updating their terms of service agreements to comply.
    </p>
    <p class="text-base leading-relaxed text-gray-500 dark:text-gray-400 mt-3">
        The European Union’s General Data Protection Regulation (G.D.P.R.) goes into effect on May 25 and is meant to ensure a common set of data rights in the European Union. It requires organizations to notify users as soon as possible of high-risk data breaches that could personally affect them.
    </p>
@endsection

@section('footer')
    <button class="relative select-none items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-100 border-blue-500 px-4 py-2 text-base rounded-md inline-flex" data-dismiss="modal">
        <span class="flex items-center">Accept</span>
    </button>
    <button class="relative select-none items-center rounded-md border font-semibold ring-offset-2 focus:outline-none focus:ring-2 text-white bg-red-600 hover:bg-red-700 focus:ring-red-100 border-red-500 px-4 py-2 text-base rounded-md inline-flex" data-dismiss="modal">
        <span class="flex items-center">Decline</span>
    </button>
@endsection
