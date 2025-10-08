<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

namespace MaarchCourrier\Tests\core;

use SrcCore\controllers\PasswordController;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use MaarchCourrier\Tests\CourrierTestCase;

class PasswordControllerTest extends CourrierTestCase
{
    private static ?string $generalConfigPath = null;
    private static $generalConfigOriginal = null;
    private static bool $restoreOriginalConfig = false;

    protected function setUp(): void
    {
        self::$generalConfigPath = (file_exists("config/config.json") ? "config/config.json" : "config/config.json.default");

        $generalConfig = file_get_contents(self::$generalConfigPath);
        $generalConfig = json_decode($generalConfig, true);
        $generalConfig['config']['privateKeyPath'] = getcwd() . '/config/mc_secret.key';
        self::$generalConfigOriginal = $generalConfig;
    }

    public function testCheckOriginalConfigIsNotEmpty()
    {
        $this->assertNotEmpty(self::$generalConfigOriginal);
    }

    public function testGetRules()
    {
        $passwordController = new PasswordController();

        $request = $this->createRequest('GET');

        $response     = $passwordController->getRules($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->rules);
        $this->assertNotEmpty($responseBody->rules);
    }

    public function testUpdateRules()
    {
        $passwordController = new PasswordController();

        $request = $this->createRequest('GET');

        $response     = $passwordController->getRules($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        // reset
        $rules = (array)$responseBody->rules;
        foreach ($rules as $key => $rule) {
            $rules[$key] = (array)$rule;
            $rule = (array)$rule;
            if ($rule['label'] == 'complexitySpecial' || $rule['label'] == 'complexityNumber' || $rule['label'] == 'complexityUpper') {
                $rules[$key]['enabled'] = false;
            }
            if ($rule['label'] == 'minLength') {
                $rules[$key]['value'] = 6;
                $rules[$key]['enabled'] = true;
            }
        }

        $fullRequest = $this->createRequestWithBody('PUT', ['rules' => $rules]);
        $response       = $passwordController->updateRules($fullRequest, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame($responseBody->success, 'success');

        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'maarch']);
        $this->assertSame($isPasswordValid, true);

        // minLength
        foreach ($rules as $key => $rule) {
            if ($rule['label'] == 'minLength') {
                $rules[$key]['value'] = 7;
                $rules[$key]['enabled'] = true;
            }
        }

        $fullRequest = $this->createRequestWithBody('PUT', ['rules' => $rules]);
        $response       = $passwordController->updateRules($fullRequest, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame($responseBody->success, 'success');

        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'maarch']);
        $this->assertSame($isPasswordValid, false);
        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'maaarch']);
        $this->assertSame($isPasswordValid, true);

        // complexityUpper
        foreach ($rules as $key => $rule) {
            if ($rule['label'] == 'complexityUpper') {
                $rules[$key]['enabled'] = true;
            }
        }

        $fullRequest = $this->createRequestWithBody('PUT', ['rules' => $rules]);
        $response       = $passwordController->updateRules($fullRequest, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame($responseBody->success, 'success');

        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'maaarch']);
        $this->assertSame($isPasswordValid, false);
        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'Maaarch']);
        $this->assertSame($isPasswordValid, true);

        // complexityNumber
        foreach ($rules as $key => $rule) {
            if ($rule['label'] == 'complexityNumber') {
                $rules[$key]['enabled'] = true;
            }
        }

        $fullRequest = $this->createRequestWithBody('PUT', ['rules' => $rules]);
        $response       = $passwordController->updateRules($fullRequest, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame($responseBody->success, 'success');

        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'Maaarch']);
        $this->assertSame($isPasswordValid, false);
        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'Maaarch1']);
        $this->assertSame($isPasswordValid, true);

        // complexitySpecial
        foreach ($rules as $key => $rule) {
            if ($rule['label'] == 'complexitySpecial') {
                $rules[$key]['enabled'] = true;
            }
        }

        $fullRequest = $this->createRequestWithBody('PUT', ['rules' => $rules]);
        $response       = $passwordController->updateRules($fullRequest, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame($responseBody->success, 'success');

        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'Maaarch1']);
        $this->assertSame($isPasswordValid, false);
        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'Maaarch1!']);
        $this->assertSame($isPasswordValid, true);

        // reset
        foreach ($rules as $key => $rule) {
            if ($rule['label'] == 'complexitySpecial' || $rule['label'] == 'complexityNumber' || $rule['label'] == 'complexityUpper') {
                $rules[$key]['enabled'] = false;
            }
            if ($rule['label'] == 'minLength') {
                $rules[$key]['value'] = 6;
                $rules[$key]['enabled'] = true;
            }
        }

        $fullRequest = $this->createRequestWithBody('PUT', ['rules' => $rules]);
        $response       = $passwordController->updateRules($fullRequest, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame($responseBody->success, 'success');

        $isPasswordValid = $passwordController->isPasswordValid(['password' => 'maarch']);
        $this->assertSame($isPasswordValid, true);
    }

    public function provideDataToEncrypt()
    {
        return [
            'lower case letters' => [
                "dataToEncrypt" => "abcdefghijklmnopqrstuvwxyz"
            ],
            'upper case letters' => [
                "dataToEncrypt" => "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
            ],
            'numbers' => [
                "dataToEncrypt" => "0123456789"
            ],
            'special characters' => [
                "dataToEncrypt" => "`~!@#$%^&*()-_=+[{]}\|;:'\",<.>/? "
            ],
            'lower and upper case letters' => [
                "dataToEncrypt" => "Maarch Courrier"
            ],
            'lower, upper case letters and numbers' => [
                "dataToEncrypt" => "Maarch Courrier 2301"
            ],
            'lower, upper case letters, numbers and special characters' => [
                "dataToEncrypt" => "Wêl¢om€ Tô Määrc|-| Cøμrr¡€r 2301"
            ],
        ];
    }

    /**
     * @dataProvider provideDataToEncrypt
     */
    public function testEncryptAndDecrypt($dataToEncrypt)
    {
        $encryptedData = PasswordController::encrypt(['dataToEncrypt' => $dataToEncrypt]);
        $decryptedData = PasswordController::decrypt(['encryptedData' => $encryptedData]);

        $this->assertNotEmpty($encryptedData);
        $this->assertNotEmpty($decryptedData);
        $this->assertSame($decryptedData, $dataToEncrypt);
    }

    /**
     * @deprecated This test function is deprecated and will be removed in future major versions.
     */
    public function testGetEncryptKeyExpectUsingVhostKey()
    {
        self::removePrivateKeyPath();

        $useVhostEncryptKey = CoreConfigModel::useVhostEncryptKey();

        $this->assertNotEmpty($useVhostEncryptKey);
        $this->assertTrue($useVhostEncryptKey);
    }

    /**
     * @deprecated This test function is deprecated and will be removed in future major versions.
     */
    public function testGetEncryptKeyExpectUsingFileKey()
    {
        $usePrivateKey = CoreConfigModel::useVhostEncryptKey();

        $this->assertEmpty($usePrivateKey);
        $this->assertFalse($usePrivateKey);
    }

    /**
     * @deprecated This test function is deprecated and will be removed in future major versions.
     * @dataProvider provideDataToEncrypt
     */
    public function testEncryptAndDecryptUsingOldCipherMethod($dataToEncrypt)
    {
        self::removePrivateKeyPath();

        $encryptedData = PasswordController::encrypt(['dataToEncrypt' => $dataToEncrypt]);
        $decryptedData = PasswordController::decrypt(['encryptedData' => $encryptedData]);

        $this->assertNotEmpty($encryptedData);
        $this->assertNotEmpty($decryptedData);
        $this->assertSame($decryptedData, $dataToEncrypt);
    }

    /**
     * @deprecated This test function is deprecated and will be removed in future major versions.
     * @dataProvider provideDataToEncrypt
     */
    public function testEncryptUsingNewCipherMethodAndDecryptUsingOldCipherMethod($dataToEncrypt)
    {
        $encryptedData = PasswordController::encrypt(['dataToEncrypt' => $dataToEncrypt]);

        self::removePrivateKeyPath();

        $decryptedData = null;
        $exceptionError = null;

        try {
            $decryptedData = PasswordController::decrypt(['encryptedData' => $encryptedData]);
        } catch (\Exception $e) {
            $exceptionError = $e->getMessage();
        }

        $this->assertNotEmpty($encryptedData);
        $this->assertNotEmpty($exceptionError);
        $this->assertEmpty($decryptedData);
    }

    /**
     * @deprecated This test function is deprecated and will be removed in future major versions.
     */
    private function removePrivateKeyPath()
    {
        $configPath = \SrcCore\models\CoreConfigModel::getConfigPath();
        $coreConfig = json_decode(file_get_contents($configPath), true);

        unset($coreConfig['config']['privateKeyPath']);
        file_put_contents($configPath, json_encode($coreConfig));
        self::$restoreOriginalConfig = true;
    }

    protected function tearDown(): void
    {
        if (!empty(self::$restoreOriginalConfig)) {
            file_put_contents(self::$generalConfigPath, json_encode(self::$generalConfigOriginal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
