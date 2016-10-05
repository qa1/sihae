<?php

namespace Sihae;

use RKA\Session;
use Sihae\Entities\Post;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;
use Doctrine\ORM\EntityManager;
use League\CommonMark\CommonMarkConverter;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PostController
{
    /**
     * @var PhpRenderer
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
     * @param PhpRenderer $renderer
     * @param EntityManager $entityManager
     * @param CommonMarkConverter $markdown
     * @param Messages $flash
     */
    public function __construct(
        PhpRenderer $renderer,
        EntityManager $entityManager,
        CommonMarkConverter $markdown,
        Messages $flash,
        Session $session
    ) {
        $this->renderer = $renderer;
        $this->entityManager = $entityManager;
        $this->markdown = $markdown;
        $this->flash = $flash;
        $this->session = $session;
    }

    /**
     * Check if there is a signed in user. We don't have user roles/permissions
     * so any old user can do what they want because they're all assumed to be
     * admins. No one should have to login to read your blog so this is OK
     *
     * @return boolean
     */
    private function isUserAuthorised() : bool
    {
        return !empty($this->session->get('username'));
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

        $limit = 4;
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

        return $this->renderer->render($response, 'layout.phtml', [
            'page' => 'post-list',
            'posts' => $parsedPosts,
            'current_page' => $page,
            'total_pages' => intdiv($total + $limit - 1, $limit),
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
        if (!$this->isUserAuthorised()) {
            return $response->withStatus(403)->withHeader('Location', '/');
        }

        return $this->renderer->render($response, 'layout.phtml', ['page' => 'post-form']);
    }

    /**
     * Save a new Post
     *
     * TODO: validation
     * TODO: prevent duplicate slugs
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function store(Request $request, Response $response) : Response
    {
        if (!$this->isUserAuthorised()) {
            return $response->withStatus(403)->withHeader('Location', '/');
        }

        $newPost = $request->getParsedBody();

        if (isset($newPost['title'], $newPost['body'])) {
            $post = new Post();
            $post->setTitle($newPost['title']);
            $post->setBody($newPost['body']);

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->flash->addMessage('success', 'Successfully created your new post!');

            return $response->withStatus(302)->withHeader('Location', '/post/' . $post->getSlug());
        }

        return $this->renderer->render($response, 'layout.phtml', [
            'page' => 'post-form',
            'post' => $newPost,
        ]);
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

        return $this->renderer->render($response, 'layout.phtml', [
            'page' => 'post',
            'post' => $parsedPost,
        ]);
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
        if (!$this->isUserAuthorised()) {
            return $response->withStatus(403)->withHeader('Location', '/');
        }

        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug]);

        if (!$post) {
            return $response->withStatus(404);
        }

        return $this->renderer->render($response, 'layout.phtml', [
            'page' => 'post-form',
            'post' => $post,
            'isEdit' => true,
        ]);
    }

    /**
     * Save updates to an existing Post
     *
     * TODO: validation
     *
     * @param Request $request
     * @param Response $response
     * @param string $slug
     * @return Response
     */
    public function update(Request $request, Response $response, string $slug) : Response
    {
        if (!$this->isUserAuthorised()) {
            return $response->withStatus(403)->withHeader('Location', '/');
        }

        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['slug' => $slug]);

        if (!$post) {
            return $response->withStatus(404);
        }

        $updatedPost = $request->getParsedBody();

        if (isset($updatedPost['title'], $updatedPost['body'])) {
            $post->setTitle($updatedPost['title']);
            $post->setBody($updatedPost['body']);

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->flash->addMessage('success', 'Successfully edited your post!');

            return $response->withStatus(302)->withHeader('Location', '/post/' . $slug);
        }

        return $this->renderer->render($response, 'layout.phtml', [
            'page' => 'post-form',
            'post' => $updatedPost,
            'isEdit' => true,
        ]);
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
        if (!$this->isUserAuthorised()) {
            return $response->withStatus(403)->withHeader('Location', '/');
        }

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
