<?php

namespace MaarchCourrier\Tests\app\contentManagement;

use ContentManagement\controllers\DocumentEditorController;
use MaarchCourrier\Tests\CourrierTestCase;

class DocumentEditorControllerTest extends CourrierTestCase
{
    public function testIpAddressIsAValidUri(): void
    {
        $ip = "192.168.0.112";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }

    public function testUrlIsAValidUri(): void
    {
        $ip = "exemple.com";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }

    public function testIpAddressWithDomainIsAValidUri(): void
    {
        $ip = "192.168.0.112/";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }

    public function testUrlWithDomainIsAValidUri(): void
    {
        $ip = "exemple.com/";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }
    public function testUrlWithMultiDomainIsAValidUri(): void
    {
        $ip = "exemple.com/test/test2";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }

    public function testSimpleStringIsValidHostname(): void
    {
        $ip = "onlyoffice";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }

    public function testSimpleStringWithDomainIsValidHostname(): void
    {
        $ip = "onlyoffice/";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }

    public function testSimpleStringWithNumberAndDomainIsValidHostname(): void
    {
        $ip = "onlyoffice23/";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider simpleStringAndNumberData
     */
    public function testSimpleStringWithNumberIsValidHostname($input): void
    {

        $result = DocumentEditorController::uriIsValid($input);

        $this->assertNotEmpty($result);
        $this->assertTrue($result);
    }
    public function simpleStringAndNumberData()
    {
        return [
            'string with 2 number' => [
                "input"             => "onlyoffice14"
            ],
            'string with 3 number' => [
                "input"             => "onlyoffice100"
            ]
        ];
    }

    public function testSimpleStringIsNoValidHostname():void
    {
        $ip = "onlyoffice!";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertFalse($result);
    }
    public function testSimpleStringWithNumberIsNoValidHostname():void
    {
        $ip = "onlyoffice23;";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertFalse($result);
    }

    public function testUrlCharacterIsNotInTheWhiteList(): void
    {
        $ip = "exemple.com*";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertFalse($result);
    }

    public function testIpCharacterIsNotInTheWhiteList(): void
    {
        $ip = "3.4.5.3£";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertFalse($result);
    }

    public function testUrlWithASpace(): void
    {
        $ip = "exem ple.com";

        $result = DocumentEditorController::uriIsValid($ip);

        $this->assertFalse($result);
    }

}
