<?php

use Slim\App;

return function (App $app) {
    $app->get('/[page/{page:[1-9][0-9]*}]', 'Sihae\PostController:index');
    $app->get('/post/new', 'Sihae\PostController:create');
    $app->post('/post/new', 'Sihae\PostController:store');
    $app->get('/post/edit/{slug}', 'Sihae\PostController:edit');
    $app->post('/post/edit/{slug}', 'Sihae\PostController:update');
    $app->get('/post/delete/{slug}', 'Sihae\PostController:delete');
    $app->get('/post/{slug}', 'Sihae\PostController:show');
};
