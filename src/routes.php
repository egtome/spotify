<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Api\v1\ListAlbumsByArtistNameAction;
use Middlewares\ValidateRequestMiddleware;

return function (App $app) {    
    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {
        // Render index view (as example to query Spotify API)
        return $container->get('renderer')->render($response, 'index.phtml', $args);
    });
    
    $app->get('/api/v1/albums', ListAlbumsByArtistNameAction::class)->add(ValidateRequestMiddleware::class);
};
