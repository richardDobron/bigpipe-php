<?php

return [

    /*
     * Adds the SetUpBigPipe middleware to the "web" middleware group. Turn it off to add the
     * middleware yourself, e.g. to a single route group.
     */
    'middleware' => true,

    'csrf' => [

        /*
         * Sends the CSRF token of the session to the browser, which adds it to every request of
         * AsyncRequest that can change data. See BigPipe::setCSRFToken().
         */
        'enabled' => true,

        'header' => 'X-CSRF-TOKEN',

        'param' => null,

        /*
         * The URL the browser gets a new token from when the token expired (419), before it sends
         * the request again. Null registers no route.
         */
        'refresh_uri' => '/bigpipe/csrf-token',

        /*
         * Answers a request of BigPipe rejected for its expired token (419) with the new token, so
         * the browser sends the request again, once.
         */
        'retry' => true,
    ],

];
