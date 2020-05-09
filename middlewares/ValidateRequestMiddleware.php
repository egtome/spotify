<?php
namespace Middlewares;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Using middleware to validate request before executing it
 *
 * @author Gino Tome
 */
class ValidateRequestMiddleware {
    /**
     * Validate request (generic as an example for now)
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        $artist_name = $request->getQueryParam('q', $default = null);
        if($artist_name === null || trim($artist_name) == ''){
            throw new \Exception('Artist name is required. Example: http://test.spotify.com/api/v1/albums?q=rammstein',400);
        }
        $response = $next($request, $response);
        return $response;
    }
}
