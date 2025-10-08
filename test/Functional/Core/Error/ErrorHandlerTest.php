<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ErrorHandlerTest
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Functional\Core\Error;

use MaarchCourrier\Core\Infrastructure\Error\ErrorHandler;
use MaarchCourrier\Tests\CourrierTestCase;
use MaarchCourrier\Tests\Functional\Core\Error\Mock\EnvironmentMock;
use MaarchCourrier\Tests\Functional\Core\Error\Mock\StubProblem;

class ErrorHandlerTest extends CourrierTestCase
{
    public function testAnExceptionIsSerialisedAsAnInternalServerProblem(): void
    {
        $environmentMock = new EnvironmentMock();

        $errorHandler = new ErrorHandler($environmentMock);

        $exception = new \Exception('A technical exception');

        $request = $this->createRequest('GET');
        $response = $errorHandler->__invoke(
            $request,
            $exception,
            true,
            true,
            true
        );


        $responseBody = json_decode($response->getBody(), true);

        $this->assertSame(
            [
                'title'   => 'An error occurred',
                'type'    => 'internalServerProblem',
                'detail'  => 'Internal server error',
                'errors'  => 'Internal server error',
                'status'  => 500,
                'lang'    => null,
                'context' => [
                    'message' => 'A technical exception'
                ]
            ],
            $responseBody
        );
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testWithDebugEnabledInternalServerProblemHasDebugFieldsInResponse(): void
    {
        $environmentMock = new EnvironmentMock();
        $environmentMock->debug = true;

        $errorHandler = new ErrorHandler($environmentMock);

        $exception = new \Exception('A technical exception');

        $request = $this->createRequest('GET');
        $response = $errorHandler->__invoke(
            $request,
            $exception,
            true,
            true,
            true
        );


        $responseBody = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('file', $responseBody);
        $this->assertArrayHasKey('line', $responseBody);
        $this->assertArrayHasKey('trace', $responseBody);

        $this->assertArrayHasKey('file', $responseBody['context']);
        $this->assertArrayHasKey('line', $responseBody['context']);
        $this->assertArrayHasKey('trace', $responseBody['context']);

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testAProblemIsSerializedWithTheCustomProblemData(): void
    {
        $environmentMock = new EnvironmentMock();

        $errorHandler = new ErrorHandler($environmentMock);

        $exception = new StubProblem('toto');

        $request = $this->createRequest('GET');
        $response = $errorHandler->__invoke(
            $request,
            $exception,
            true,
            true,
            true
        );


        $responseBody = json_decode($response->getBody(), true);

        $this->assertSame(
            [
                'title'   => 'An error occurred',
                'type'    => 'stubProblem',
                'detail'  => 'My custom problem : toto',
                'errors'  => 'My custom problem : toto',
                'status'  => 418,
                'lang'    => null,
                'context' => [
                    'value' => 'toto'
                ]
            ],
            $responseBody
        );
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testWithDebugEnabledCustomProblemHasDebugFieldsInResponse(): void
    {
        $environmentMock = new EnvironmentMock();
        $environmentMock->debug = true;

        $errorHandler = new ErrorHandler($environmentMock);

        $exception = new StubProblem('toto');

        $request = $this->createRequest('GET');
        $response = $errorHandler->__invoke(
            $request,
            $exception,
            true,
            true,
            true
        );


        $responseBody = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('file', $responseBody);
        $this->assertArrayHasKey('line', $responseBody);
        $this->assertArrayHasKey('trace', $responseBody);

        $this->assertSame(418, $response->getStatusCode());
    }
}
