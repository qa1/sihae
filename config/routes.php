<?php

declare(strict_types=1);

use Sihae\Actions\ArchivedPostsAction;
use Sihae\Actions\CreatePostAction;
use Sihae\Actions\DeletePostAction;
use Sihae\Actions\EditPostAction;
use Sihae\Actions\EditPostFormAction;
use Sihae\Actions\LoginAction;
use Sihae\Actions\LoginFormAction;
use Sihae\Actions\LogoutAction;
use Sihae\Actions\PostFormAction;
use Sihae\Actions\PostListAction;
use Sihae\Actions\PostListTaggedAction;
use Sihae\Actions\RegisterUserAction;
use Sihae\Actions\RegistrationFormAction;
use Sihae\Actions\TagListAction;
use Sihae\Actions\ViewPostAction;
use Sihae\Middleware\AuthMiddleware;
use Sihae\Middleware\PostLocator;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $app->get('/[page/{page:[1-9][0-9]*}]', PostListAction::class);

    $app->group('/post/admin', function (RouteCollectorProxy $group): void {
        $group->get('/new', PostFormAction::class);
        $group->post('/new', CreatePostAction::class);

        $group->group('', function (RouteCollectorProxy $group): void {
            $group->get('/edit/{slug:[a-zA-Z\d\s\-_\-]+}', EditPostFormAction::class);
            $group->post('/edit/{slug:[a-zA-Z\d\s\-_\-]+}', EditPostAction::class);
            $group->post('/delete/{slug:[a-zA-Z\d\s\-_\-]+}', DeletePostAction::class);
        })->add(PostLocator::class);
    })->add(AuthMiddleware::class);

    $app->get('/post/{slug:[a-zA-Z\d\s\-_\-]+}', ViewPostAction::class)
        ->add(PostLocator::class);

    $app->get('/tagged/{slug:[a-zA-Z\d\s\-_\-]+}[/page/{page:[1-9][0-9]*}]', PostListTaggedAction::class);

    $app->get('/archive', ArchivedPostsAction::class);
    $app->get('/tags', TagListAction::class);

    $app->get('/login', LoginFormAction::class);
    $app->post('/login', LoginAction::class);
    $app->get('/logout', LogoutAction::class);

    if ((bool) getenv('ENABLE_REGISTRATION') === true) {
        $app->get('/register', RegistrationFormAction::class);
        $app->post('/register', RegisterUserAction::class);
    }
};
