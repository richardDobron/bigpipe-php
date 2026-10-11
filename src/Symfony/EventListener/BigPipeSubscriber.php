<?php

namespace dobron\BigPipe\Symfony\EventListener;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Context;
use dobron\BigPipe\Symfony\AsyncResponse as SymfonyAsyncResponse;
use dobron\BigPipe\Symfony\DialogResponse as SymfonyDialogResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Sends the CSRF token to the browser, recovers an expired one and turns a response of BigPipe a
 * controller returns into a response of Symfony.
 */
class BigPipeSubscriber implements EventSubscriberInterface
{
    /**
     * @param array{token_id: ?string, header: ?string, param: ?string, refresh_uri?: ?string, retry?: bool} $csrf
     */
    public function __construct(
        private array $csrf,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Before the router, so the refresh URL needs no route.
            KernelEvents::REQUEST => [['onCsrfRefresh', 64], ['onKernelRequest', 0]],
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * Answers the refresh URL with a new token, see BigPipe::setCSRFToken().
     */
    public function onCsrfRefresh(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $uri = $this->csrf['refresh_uri'] ?? null;

        if (
            !$event->isMainRequest()
            || $uri === null
            || !$request->isMethod('GET')
            || $request->getPathInfo() !== $uri
            || ($arguments = $this->csrfArguments($request)) === null
        ) {
            return;
        }

        $response = (new AsyncResponse(new Context()))
            ->define(BigPipe::CSRF_TOKEN_MODULE, BigPipe::csrfTokenModule(...$arguments));

        $event->setResponse(new Response($response->buildResponseString(), 200, AsyncResponse::headers()));
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || ($arguments = $this->csrfArguments($event->getRequest())) === null) {
            return;
        }

        BigPipe::setCSRFToken(...$arguments);
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof SymfonyAsyncResponse || $result instanceof SymfonyDialogResponse) {
            $event->setResponse($result->send());
        } elseif ($result instanceof AsyncResponse) {
            $event->setResponse(new Response($result->buildResponseString(), 200, AsyncResponse::headers()));
        }
    }

    /**
     * Answers a request of BigPipe rejected for its CSRF token (an HttpException with 419) with the
     * new token, so the browser sends the request again, once.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (
            !($this->csrf['retry'] ?? true)
            || !$exception instanceof HttpExceptionInterface
            || $exception->getStatusCode() !== 419
            || !$request->isXmlHttpRequest()
            || ($arguments = $this->csrfArguments($request)) === null
        ) {
            return;
        }

        $response = (new AsyncResponse(new Context()))->retryWithCSRFToken(...$arguments);

        $event->allowCustomResponseCode();
        $event->setResponse(new Response($response->buildResponseString(), 200, AsyncResponse::headers()));
    }

    /**
     * The arguments of BigPipe::setCSRFToken(), or null when no token is sent.
     *
     * @return null|array{string, ?string, ?string, ?string}
     */
    private function csrfArguments(Request $request): ?array
    {
        $tokenId = $this->csrf['token_id'] ?? null;

        if ($tokenId === null || $this->csrfTokenManager === null) {
            return null;
        }

        $uri = $this->csrf['refresh_uri'] ?? null;

        return [
            $this->csrfTokenManager->getToken($tokenId)->getValue(),
            $this->csrf['header'] ?? null,
            $this->csrf['param'] ?? null,
            $uri === null ? null : $request->getBaseUrl() . $uri,
        ];
    }
}
