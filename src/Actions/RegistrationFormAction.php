<?php

declare(strict_types=1);

namespace Sihae\Actions;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RKA\Session;
use Sihae\Renderer;

final class RegistrationFormAction implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private Renderer $renderer;
    private Session $session;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        Renderer $renderer,
        Session $session
    ) {
        $this->responseFactory = $responseFactory;
        $this->renderer = $renderer;
        $this->session = $session;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->session->get('token')) {
            return $this->responseFactory->createResponse(302)
                ->withHeader('Location', '/');
        }

        return $this->renderer->render(
            $this->responseFactory->createResponse(),
            'register'
        );
    }
}
