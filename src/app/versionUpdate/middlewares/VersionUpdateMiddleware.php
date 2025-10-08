<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Version Update Middleware
 * @author dev@maarch.org
 */

namespace VersionUpdate\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use VersionUpdate\controllers\VersionUpdateController;

class VersionUpdateMiddleware implements MiddlewareInterface
{
    /**
     * Methode From MiddlewareInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $response = new \SrcCore\http\Response();

        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $route->getPattern();

        $currentMethod = $route->getMethods()[0];
        $currentRoute = $route->getPattern();

        $control = VersionUpdateMiddleware::middlewareControl($currentMethod, $currentRoute);
        if (!empty($control)) {
            return $response->withHeader('Retry-After', '5 minutes')->withStatus(503)->withJson($control['response']);
        }

        return $requestHandler->handle($request);
    }

    /**
     * Middleware logique
     * @param   string  $httpMethod
     * @param   string  $currentRoute   An API route
     * @return  array   Empty or Response array
     */
    public static function middlewareControl(string $httpMethod, string $currentRoute)
    {
        $return = [];
        if (!in_array($httpMethod . $currentRoute, VersionUpdateController::ROUTES_WITHOUT_MIGRATION)) {
            if (VersionUpdateController::isMigrating()) {
                $return['response'] = [
                    "errors"        => "Service unavailable : migration in progress",
                    "lang"          => "migrationProcessing",
                    'migrating'     => true
                ];
            }
        }

        return $return;
    }
}
