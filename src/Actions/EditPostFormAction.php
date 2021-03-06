<?php

declare(strict_types=1);

namespace Sihae\Actions;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sihae\Entities\Post;
use Sihae\Renderer;
use Sihae\Repositories\TagRepository;

final class EditPostFormAction implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private Renderer $renderer;
    private TagRepository $tagRepository;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        Renderer $renderer,
        TagRepository $tagRepository
    ) {
        $this->responseFactory = $responseFactory;
        $this->renderer = $renderer;
        $this->tagRepository = $tagRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $post = $request->getAttribute('post');

        if (!$post instanceof Post) {
            return $this->responseFactory->createResponse(404);
        }

        $tags = $this->tagRepository->findAllAsArray();

        $selectedTags = $this->tagRepository->findAllForPostAsArray($post);

        return $this->renderer->render(
            $this->responseFactory->createResponse(),
            'editor',
            [
                'post' => $post,
                'isEdit' => true,
                'tag_data' => json_encode(
                    [
                        'tags' => $tags,
                        'selected_tags' => $selectedTags,
                    ],
                    JSON_THROW_ON_ERROR
                ),
            ]
        );
    }
}
