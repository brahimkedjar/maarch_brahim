<?php

namespace MaarchCourrier\Tests\Unit\SignatureBook\Infrastructure\Config;

use MaarchCourrier\SignatureBook\Infrastructure\Controller\RetrieveConfigController;
use MaarchCourrier\Tests\CourrierTestCase;
use SrcCore\http\Response;

class RetrieveConfigControllerTest extends CourrierTestCase
{
    public function testGetSignatureBookConfigFromApiRouteExpectNoExceptions(): void
    {
        $retrieveConfigController = new RetrieveConfigController();
        $request = $this->createRequest('GET');

        $response = $retrieveConfigController->getConfig($request, new Response());
        $config = json_decode((string)$response->getBody(), true);

        $this->assertNotEmpty($config);
        $this->assertIsArray($config);
        $this->assertArrayHasKey('isNewInternalParaph', $config);
    }
}
