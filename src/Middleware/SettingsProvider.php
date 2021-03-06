<?php

declare(strict_types=1);

namespace Sihae\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sihae\Renderer;

/**
 * Provides the Sihae settings to the Renderer
 */
final class SettingsProvider implements MiddlewareInterface
{
    private Renderer $renderer;

    /**
     * @var array<string, mixed>
     */
    private array $settings;

    /**
     * @param Renderer $renderer
     * @param array<string, mixed> $settings
     */
    public function __construct(Renderer $renderer, array $settings)
    {
        $this->renderer = $renderer;
        $this->settings = $settings;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->renderer->addData(['settings' => $this->settings]);

        return $handler->handle($request);
    }
}
