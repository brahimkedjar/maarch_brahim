<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ErrorHandler class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\Core\Infrastructure\Error;

use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\Problem\InternalServerProblem;
use MaarchCourrier\Core\Domain\Problem\Problem;
use MaarchCourrier\Core\Infrastructure\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use SrcCore\http\Response;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private ?EnvironmentInterface $environnement = null
    ) {
        if ($this->environnement === null) {
            $this->environnement = new Environment();
        }
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $response = new Response();

        $problem = $exception;
        $debug = $this->environnement->isDebug();

        if (!$exception instanceof Problem) {
            $problem = new InternalServerProblem($exception, $debug);
        }


        $payload = $problem->jsonSerialize($debug);

        return $response
            ->withStatus($problem->getStatus())
            ->withJson($payload);
    }
}
