<?php

namespace MaarchCourrier\Tests\core;

use MaarchCourrier\Tests\CourrierTestCase;
use SrcCore\controllers\AuthenticationController;
use SrcCore\controllers\InstallerController;
use SrcCore\http\Response;

class InstallerControllerTest extends CourrierTestCase
{
    private static string $custom = 'create_custom_test';

    protected function tearDown(): void
    {
        $dir = 'custom/' . self::$custom;
        system('rm -rf ' . escapeshellarg($dir));
        $dir = self::$custom;
        system('rm -rf ' . escapeshellarg($dir));
        $dir = "custom/custom.json";
        $custom = json_decode(file_get_contents($dir), true);
        foreach ($custom as $key => $value) {
            if ($value['id'] == self::$custom) {
                unset($custom[$key]);
            }
        }
        file_put_contents($dir, json_encode($custom, JSON_PRETTY_PRINT));
        $GLOBALS['customId'] = null;
        CoreConfigModelHelper::setCustomId(null);
    }

    public function testCreatingACustomPrivateKeyIsNotTheDefaultKey()
    {

        $installController = new InstallerController();
        $authController = new AuthenticationController();
        $args = [
            "customId" => self::$custom
        ];

        $request = $this->createRequestWithBody('POST', $args);
        $response = $installController->createCustom($request, new Response());
        $this->assertSame(204, $response->getStatusCode());

        $this->changeCustom(self::$custom);

        $request = $this->createRequest('GET');
        $info = $authController->getInformations($request, new Response());
        $responseContent = json_decode($info->getBody(), true);


        $this->assertSame(200, $info->getStatusCode());
        $this->assertSame(false, $responseContent['changeKey']);
    }

}
