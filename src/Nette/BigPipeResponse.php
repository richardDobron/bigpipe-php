<?php

namespace dobron\BigPipe\Nette;

use dobron\BigPipe\AsyncResponse;
use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

/**
 * Sends an AsyncResponse or a DialogResponse from a presenter, streamed when the request accepts it:
 * `$this->sendResponse(new BigPipeResponse($response));`.
 */
class BigPipeResponse implements Response
{
    public function __construct(private AsyncResponse $response)
    {
    }

    public function getResponse(): AsyncResponse
    {
        return $this->response;
    }

    /**
     * @throws \Throwable
     */
    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        foreach (AsyncResponse::headers() as $name => $value) {
            $httpResponse->setHeader($name, $value);
        }

        $stream = $httpRequest->getQuery(AsyncResponse::STREAM_PARAM) !== null
            || $httpRequest->getPost(AsyncResponse::STREAM_PARAM) !== null;

        if ($stream) {
            $httpResponse->setHeader('X-Accel-Buffering', 'no');
            $this->response->stream();
        } else {
            echo $this->response->buildResponseString();
        }
    }
}
