<?php

namespace Sihae\Controllers;

use RKA\Session;
use Sihae\Renderer;
use Sihae\Entities\Post;
use Sihae\Entities\User;
use Slim\Flash\Messages;
use Sihae\Validators\Validator;
use Doctrine\ORM\EntityManager;
use League\CommonMark\CommonMarkConverter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PostController
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CommonMarkConverter
     */
    private $markdown;

    /**
     * @var Messages
     */
    private $flash;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Renderer $renderer
     * @param EntityManager $entityManager
     * @param CommonMarkConverter $markdown
     * @param Messages $flash
     * @param Validator $validator
     * @param Session $session
     */
    public function __construct(
        Renderer $renderer,
        EntityManager $entityManager,
        CommonMarkConverter $markdown,
        Messages $flash,
        Validator $validator,
        Session $session
    ) {
        $this->renderer = $renderer;
        $this->entityManager = $entityManager;
        $this->markdown = $markdown;
        $this->flash = $flash;
        $this->validator = $validator;
        $this->session = $session;
    }

    /**
     * List all Posts
     *
     * @param Request $request
     * @param Response $response
     * @param integer $page
     * @return Response
     */
    public function index(Request $request, Response $response, int $page = 1) : Response
    {
        $postRepository = $this->entityManager->getRepository(Post::class);

        $limit = 8;
        $offset = $limit * ($page - 1);

        $posts = $postRepository->findBy([], ['date_created' => 'DESC'], $limit, $offset);

        $parsedPosts = array_map(function (Post $post) : Post {
            $parsedBody = $this->markdown->convertToHtml($post->getBody());

            return $post->setBody($parsedBody);
        }, $posts);

        $total = $postRepository->createQueryBuilder('Post')
            ->select('COUNT(Post.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->renderer->render($response, 'post-list', [
            'posts' => $parsedPosts,
            'current_page' => $page,
            'total_pages' => (int) ceil($total / $limit) ?: 1,
        ]);
    }

    /**
     * Show form for creating a new Post
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response) : Response
    {
        return $this->renderer->render($response, 'post-form');
    }

    /**
     * Save a new Post
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function store(Request $request, Response $response) : Response
    {
        $newPost = $request->getParsedBody();

        $post = new Post();
        $post->setTitle($newPost['title']);
        $post->setBody($newPost['body']);

        if (!$this->validator->isValid($newPost)) {
            return $this->renderer->render($response, 'post-form', [
                'post' => $post,
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $token = $this->session->get('token');
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['token' => $token]);

        $post->setUser($user);

        // if there is already a post with the slug we just generated, generate a new one
        if ($this->entityManager->getRepository(Post::class)->findOneBy(['slug' => $post->getSlug()])) {
            $post->regenerateSlug();
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $this->flash->addMessage('success', 'Successfully created your new post!');

        return $response->withStatus(302)->withHeader('Location', '/post/' . $post->getSlug());
    }

    /**
     * Show a single Post
     *
     * @param Request $request
     * @param Response $response
     * @param string $slug
     * @return Response
     */
    public function show(Request $request, Response $response, string $slug) : Response
    {
        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug]);

        if (!$post) {
            return $response->withStatus(404);
        }

        $parsedBody = $this->markdown->convertToHtml($post->getBody());
        $parsedPost = $post->setBody($parsedBody);

        return $this->renderer->render($response, 'post', ['post' => $parsedPost]);
    }

    /**
     * Show the form to edit an existing Post
     *
     * @param Request $request
     * @param Response $response
     * @param string $slug
     * @return Response
     */
    public function edit(Request $request, Response $response, string $slug) : Response
    {
        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug]);

        if (!$post) {
            return $response->withStatus(404);
        }

        return $this->renderer->render($response, 'post-form', ['post' => $post, 'isEdit' => true]);
    }

    /**
     * Save updates to an existing Post
     *
     * @param Request $request
     * @param Response $response
     * @param string $slug
     * @return Response
     */
    public function update(Request $request, Response $response, string $slug) : Response
    {
        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug]);

        if (!$post) {
            return $response->withStatus(404);
        }

        $updatedPost = $request->getParsedBody();

        $post->setTitle($updatedPost['title']);
        $post->setBody($updatedPost['body']);

        if (!$this->validator->isValid($updatedPost)) {
            return $this->renderer->render($response, 'post-form', [
                'post' => $post,
                'errors' => $this->validator->getErrors(),
                'isEdit' => true,
            ]);
        }

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $this->flash->addMessage('success', 'Successfully edited your post!');

        return $response->withStatus(302)->withHeader('Location', '/post/' . $slug);
    }

    /**
     * Delete a Post
     *
     * @param Request $request
     * @param Response $response
     * @param string $slug
     * @return Response
     */
    public function delete(Request $request, Response $response, string $slug) : Response
    {
        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug]);

        if (!$post) {
            return $response->withStatus(404);
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();

        $this->flash->addMessage('success', 'Successfully deleted your post!');

        return $response->withStatus(302)->withHeader('Location', '/');
    }
}