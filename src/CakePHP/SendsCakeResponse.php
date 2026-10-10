<?php

namespace dobron\BigPipe\CakePHP;

use Cake\Http\CallbackStream;
use Cake\Http\Response;
use Cake\Routing\Router;
use dobron\BigPipe\Psr\ResponseFactory;

/**
 * Turns a response of BigPipe into a response of CakePHP: `return $response->send();`.
 */
trait SendsCakeResponse
{
    /**
     * Whether the current request accepts a streamed response. Reads the request of the router.
     */
    public static function isStreamRequested(): bool
    {
        $request = Router::getRequest();

        if ($request !== null) {
            return ResponseFactory::isStreamRequested($request);
        }

        return parent::isStreamRequested();
    }

    /**
     * The response for CakePHP, streamed when the request accepts it, see stream().
     *
     * @throws \Throwable
     */
    public function send(int $status = 200): Response
    {
        $response = new Response(['status' => $status, 'charset' => 'UTF-8']);

        foreach (static::headers() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if (static::isStreamRequested()) {
            return $response
                ->withHeader('X-Accel-Buffering', 'no')
                ->withBody(new CallbackStream(fn () => $this->stream()));
        }

        return $response->withStringBody($this->buildResponseString());
    }
}
