<?php

namespace dobron\BigPipe\Symfony\EventListener;

use dobron\BigPipe\AsyncResponse;
use dobron\BigPipe\BigPipe;
use dobron\BigPipe\Symfony\AsyncResponse as SymfonyAsyncResponse;
use dobron\BigPipe\Symfony\DialogResponse as SymfonyDialogResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Sends the CSRF token to the browser and turns a response of BigPipe a controller returns into a
 * response of Symfony.
 */
class BigPipeSubscriber implements EventSubscriberInterface
{
    /**
     * @param array{token_id: ?string, header: ?string, param: ?string} $csrf
     */
    public function __construct(
        private array $csrf,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::VIEW => 'onKernelView',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $tokenId = $this->csrf['token_id'] ?? null;

        if (!$event->isMainRequest() || $tokenId === null || $this->csrfTokenManager === null) {
            return;
        }

        BigPipe::setCSRFToken(
            $this->csrfTokenManager->getToken($tokenId)->getValue(),
            $this->csrf['header'] ?? null,
            $this->csrf['param'] ?? null
        );
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
}
