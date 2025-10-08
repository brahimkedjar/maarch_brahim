<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

namespace MaarchCourrier\Tests\app\external\signatoryBook;

use DOMDocument;
use DOMException;
use Exception;
use ExternalSignatoryBook\controllers\FastParapheurController;
use MaarchCourrier\Tests\CourrierTestCase;

use function PHPUnit\Framework\assertArrayNotHasKey;

class FastParapheurControllerTest extends CourrierTestCase
{
    private static $remoteSignatoryBookPath = null;
    private static $defaultRemoteSignatoryBookPath = "modules/visa/xml/remoteSignatoryBooks.xml.default";
    private static $generalFileConfigOriginalXml = null;
    private static $signStep = [];
    private static $visaStep = [];

    protected function setUp(): void
    {
        self::$remoteSignatoryBookPath = "modules/visa/xml/remoteSignatoryBooks.xml";
        self::$generalFileConfigOriginalXml = file_get_contents(self::$defaultRemoteSignatoryBookPath);
        file_put_contents(self::$remoteSignatoryBookPath, self::$generalFileConfigOriginalXml);

        self::$signStep = [
            [
                "resId"                => 100,
                "mainDocument"         => true,
                "externalId"           => "user1@maarch.test",
                "sequence"             => 0,
                "action"               => "sign",
                "signatureMode"        => "sign",
                "signaturePositions"   => [
                    [
                        "sequence"     => 0,
                        "page"         => 1,
                        "positionX"    => 27,
                        "positionY"    => 70,
                        "mainDocument" => false,
                        "resId"        => 100
                    ]
                ],
                "datePositions"        => [],
                "externalInformations" => null
            ]
        ];

        self::$visaStep = [
            [
                "resId"                => 100,
                "mainDocument"         => true,
                "externalId"           => "user1@maarch.test",
                "sequence"             => 0,
                "action"               => "visa",
                "signatureMode"        => "visa",
                "signaturePositions"   => [
                    [
                        "sequence"     => 0,
                        "page"         => 1,
                        "positionX"    => 27,
                        "positionY"    => 70,
                        "mainDocument" => false,
                        "resId"        => 100
                    ]
                ],
                "datePositions"        => [],
                "externalInformations" => null
            ]
        ];
    }

    protected function tearDown(): void
    {
        if (!empty(self::$remoteSignatoryBookPath) && file_exists(self::$remoteSignatoryBookPath)) {
            unlink(self::$remoteSignatoryBookPath);
        }
    }

    private function enableFastParapheurSignatoryBook(): void
    {
        $content = file_get_contents(self::$remoteSignatoryBookPath);
        $search = "<signatoryBookEnabled>maarchParapheur</signatoryBookEnabled>";
        $replace = "<signatoryBookEnabled>fastParapheur</signatoryBookEnabled>";
        $content = str_replace($search, $replace, $content);
        file_put_contents(self::$remoteSignatoryBookPath, $content);
    }

    private function enableOptionOtp(): void
    {
        $content = file_get_contents(self::$remoteSignatoryBookPath);
        $search = "<optionOtp>false</optionOtp>";
        $replace = "<optionOtp>true</optionOtp>";
        $content = str_replace($search, $replace, $content);
        file_put_contents(self::$remoteSignatoryBookPath, $content);
    }


    /**
     * @return void
     * @throws Exception
     */
    public function testCheckSignatoryBookFileIsMissing(): void
    {
        // Arrange
        unlink(self::$remoteSignatoryBookPath);

        // Act
        $fastConfig = FastParapheurController::getConfig();

        // Assert
        $this->assertNotEmpty($fastConfig);
        $this->assertNotEmpty($fastConfig['code'], "Le code erreur n'existe pas");
        $this->assertNotEmpty($fastConfig['errors'], "Le message erreur n'existe pas");

        $this->assertSame($fastConfig['code'], 400);
        $this->assertSame($fastConfig['errors'], "SignatoryBooks configuration file missing or empty");
    }

    /**
     * @return array[]
     * @throws DOMException
     */
    public function provideConfigFileWithoutFastParapheurConfig(): array
    {
        $xml = new DOMDocument("1.0", "utf-8");
        $xmlRoot = $xml->createElement("root");
        $xmlSignatoryBookEnabled = $xml->createElement('signatoryBookEnabled', 'fastParapheur');
        $xmlSignatoryBook = $xml->createElement('signatoryBook');

        $xmlRoot->appendChild($xmlSignatoryBookEnabled);
        $xmlRoot->appendChild($xmlSignatoryBook);
        $xml->appendChild($xmlRoot);

        return [
            'Remote Signatory Books file data without FastParapheur' => [
                'input' => $xml->saveXML(),
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'FastParapheur configuration is missing'
                ]
            ]
        ];
    }

    /**
     * @dataProvider provideConfigFileWithoutFastParapheurConfig
     */
    public function testCheckFastParapheurConfigIsMissing($input, $expectedOutput): void
    {
        // Arrange
        unlink(self::$remoteSignatoryBookPath);
        file_put_contents(self::$remoteSignatoryBookPath, $input);

        // Act
        $fastConfig = FastParapheurController::getConfig();

        // Assert
        $this->assertNotEmpty($fastConfig);
        $this->assertIsArray($fastConfig, "La configuration n'est pas un tableau");
        $this->assertNotEmpty($fastConfig['code'], "Le code erreur n'existe pas");
        $this->assertNotEmpty($fastConfig['errors'], "Le message erreur n'existe pas");

        $this->assertSame($fastConfig['code'], $expectedOutput['code']);
        $this->assertSame($fastConfig['errors'], $expectedOutput['errors']);
    }

    /**
     * @return array
     */
    public function provideAnMissingKeysFromConfig(): array
    {
        $doc = new DOMDocument();
        $xmlStr = simplexml_load_file(self::$defaultRemoteSignatoryBookPath)->asXML();
        $doc->loadXML($xmlStr);

        $signatoryBookNode = $doc->getElementsByTagName('root')->item(0)->getElementsByTagName('signatoryBook')->item(3);

        // remove workflowTypes node
        $workflowTypesNode = $signatoryBookNode->getElementsByTagName('workflowTypes')->item(0);
        $signatoryBookNode->removeChild($workflowTypesNode);
        $xmlStrWithoutWorkflowTypes = $doc->saveXML();

        // put back workflowTypes and remove subscriberId
        $subscriberIdNode = $signatoryBookNode->getElementsByTagName('subscriberId')->item(0);
        $signatoryBookNode->appendChild($workflowTypesNode);
        $signatoryBookNode->removeChild($subscriberIdNode);
        $xmlStrWithoutSubscriberId = $doc->saveXML();

        // put back subscriberId and remove url
        $urlNode = $signatoryBookNode->getElementsByTagName('url')->item(0);
        $signatoryBookNode->appendChild($subscriberIdNode);
        $signatoryBookNode->removeChild($urlNode);
        $xmlStrWithoutUrl = $doc->saveXML();

        // put back url and remove certPath
        $certPathNode = $signatoryBookNode->getElementsByTagName('certPath')->item(0);
        $signatoryBookNode->appendChild($urlNode);
        $signatoryBookNode->removeChild($certPathNode);
        $xmlStrWithoutCertPath = $doc->saveXML();

        // put back certPath and remove certPass
        $certPassNode = $signatoryBookNode->getElementsByTagName('certPass')->item(0);
        $signatoryBookNode->appendChild($certPathNode);
        $signatoryBookNode->removeChild($certPassNode);
        $xmlStrWithoutCertPass = $doc->saveXML();

        // put back certPass and remove certType
        $certTypeNode = $signatoryBookNode->getElementsByTagName('certType')->item(0);
        $signatoryBookNode->appendChild($certPassNode);
        $signatoryBookNode->removeChild($certTypeNode);
        $xmlStrWithoutCertType = $doc->saveXML();

        // put back certType and remove validatedState
        $validatedStateNode = $signatoryBookNode->getElementsByTagName('validatedState')->item(0);
        $signatoryBookNode->appendChild($certTypeNode);
        $signatoryBookNode->removeChild($validatedStateNode);
        $xmlStrWithoutValidatedState = $doc->saveXML();

        // put back validatedState and remove refusedState
        $refusedStateNode = $signatoryBookNode->getElementsByTagName('refusedState')->item(0);
        $signatoryBookNode->appendChild($validatedStateNode);
        $signatoryBookNode->removeChild($refusedStateNode);
        $xmlStrWithoutRefusedState = $doc->saveXML();

        // put back refusedState and remove optionOtp
        $optionOtpNode = $signatoryBookNode->getElementsByTagName('optionOtp')->item(0);
        $signatoryBookNode->appendChild($refusedStateNode);
        $signatoryBookNode->removeChild($optionOtpNode);
        $xmlStrWithoutOptionOtp = $doc->saveXML();

        return [
            'Without workflowTypes' => [
                'input' => $xmlStrWithoutWorkflowTypes,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'workflowTypes not found for FastParapheur'
                ]
            ],
            'Without subscriberId' => [
                'input' => $xmlStrWithoutSubscriberId,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'subscriberId not found for FastParapheur'
                ]
            ],
            'Without url' => [
                'input' => $xmlStrWithoutUrl,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'url not found for FastParapheur'
                ]
            ],
            'Without certPath' => [
                'input' => $xmlStrWithoutCertPath,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'certPath not found for FastParapheur'
                ]
            ],
            'Without certPass' => [
                'input' => $xmlStrWithoutCertPass,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'certPass not found for FastParapheur'
                ]
            ],
            'Without certType' => [
                'input' => $xmlStrWithoutCertType,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'certType not found for FastParapheur'
                ]
            ],
            'Without validatedState' => [
                'input' => $xmlStrWithoutValidatedState,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'validatedState not found for FastParapheur'
                ]
            ],
            'Without refusedState' => [
                'input' => $xmlStrWithoutRefusedState,
                'expectedOutput' => [
                    'code' => 500,
                    'errors' => 'refusedState not found for FastParapheur'
                ]
            ]
        ];
    }

    /**
     * @dataProvider provideAnMissingKeysFromConfig
     */
    public function testCheckFastParapheurMissingConfigKeys($input, $expectedOutput): void
    {
        // Arrange
        unlink(self::$remoteSignatoryBookPath);
        file_put_contents(self::$remoteSignatoryBookPath, $input);

        // Act
        $fastConfig = FastParapheurController::getConfig();

        // Assert
        $this->assertNotEmpty($fastConfig);
        $this->assertIsArray($fastConfig);

        // Assert
        $this->assertNotEmpty($fastConfig);
        $this->assertIsArray($fastConfig, "La configuration n'est pas un tableau");
        $this->assertNotEmpty($fastConfig['code'], "Le code erreur n'existe pas");
        $this->assertNotEmpty($fastConfig['errors'], "Le message erreur n'existe pas");

        $this->assertSame($fastConfig['code'], $expectedOutput['code']);
        $this->assertSame($fastConfig['errors'], $expectedOutput['errors']);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetOptionOtp(): void
    {
        $fastConfig = FastParapheurController::getConfig();
        $this->assertNotEmpty($fastConfig);
        $this->assertIsArray($fastConfig, "La configuration n'est pas un tableau");
        $this->assertArrayHasKey('optionOtp', $fastConfig, "La configuration ne contient pas 'optionOtp' comme clé");
        $this->assertSame($fastConfig['optionOtp'], 'false', "La configuration OTP n'est pas désactivé par défault");
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCannotPrepareIntegratedWorkflowStepsWhenStepsAreEmpty(): void
    {
        $steps = FastParapheurController::prepareSteps([]);
        $this->assertIsArray($steps);
        $this->assertArrayHasKey('error', $steps);
        $this->assertNotEmpty($steps['error']);
        $this->assertSame("steps is empty", $steps['error']);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCannotPrepareIntegratedWorkflowStepsWhenResIdNotFoundInSteps(): void
    {
        $steps = [
            [
                "externalId"    => "signataire@maarch.org",
                "sequence"      => 0,
                "action"        => "sign",
                "signatureMode" => "sign"
            ]
        ];

        $preparedSteps = FastParapheurController::prepareSteps($steps);

        $this->assertIsArray($preparedSteps);
        $this->assertArrayHasKey('error', $preparedSteps);
        $this->assertNotEmpty($preparedSteps['error']);
        $this->assertSame("no resId found in steps", $preparedSteps['error']);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCannotPrepareIntegratedWorkflowStepsWhenExternalUserFoundAndOtpOptionIsDisable(): void
    {
        $steps = [
            [
                "resId"                => 100,
                "mainDocument"         => true,
                "sequence"             => 0,
                "action"               => "sign",
                "signatureMode"        => "sign",
                "signaturePositions"   => [],
                "datePositions"        => [],
                "externalInformations" => [
                    "firstname"      => "Jenny",
                    "lastname"       => "JANE",
                    "email"          => "jjane@maarch.test",
                    "phone"          => "+9900000000",
                    "sourceId"       => 1,
                    "type"           => "fast",
                    "role"           => "sign",
                    "availableRoles" => [
                        "sign"
                    ]
                ]
            ]
        ];

        $preparedSteps = FastParapheurController::prepareSteps($steps);

        $this->assertIsArray($preparedSteps);
        $this->assertArrayHasKey('error', $preparedSteps);
        $this->assertNotEmpty($preparedSteps['error']);
        $this->assertSame(_EXTERNAL_USER_FOUND_BUT_OPTION_OTP_DISABLE, $preparedSteps['error']);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCanGetThePreparedSteps(): void
    {
        $this->enableFastParapheurSignatoryBook();
        $this->enableOptionOtp();
        $steps = [
            [
                "resId"                => 100,
                "mainDocument"         => true,
                "sequence"             => 0,
                "action"               => "sign",
                "signatureMode"        => "sign",
                "signaturePositions"   => [],
                "datePositions"        => [],
                "externalInformations" => [
                    "firstname"      => "Jenny",
                    "lastname"       => "JANE",
                    "email"          => "jjane@maarch.test",
                    "phone"          => "+9900000000",
                    "sourceId"       => 1,
                    "type"           => "fast",
                    "role"           => "sign",
                    "availableRoles" => [
                        "sign"
                    ]
                ]
            ],
            [
                "resId"                => 100,
                "mainDocument"         => true,
                "externalId"           => "signataire@maarch.org",
                "sequence"             => 1,
                "action"               => "sign",
                "signatureMode"        => "sign",
                "signaturePositions"   => [],
                "datePositions"        => [],
                "externalInformations" => null
            ]
        ];

        $preparedSteps = FastParapheurController::prepareSteps($steps);

        $this->assertIsArray($preparedSteps);
        $this->assertNotEmpty($preparedSteps[0]);
        $this->assertSame("sign", $preparedSteps[0]['mode']);
        $this->assertSame("externalOTP", $preparedSteps[0]['type']);
        $this->assertSame("+9900000000", $preparedSteps[0]['phone']);
        $this->assertSame("jjane@maarch.test", $preparedSteps[0]['email']);
        $this->assertSame("Jenny", $preparedSteps[0]['firstname']);
        $this->assertSame("JANE", $preparedSteps[0]['lastname']);
        $this->assertNotEmpty($preparedSteps[1]);
        $this->assertSame("signataire@maarch.org", $preparedSteps[1]['id']);
        $this->assertSame("fastParapheurUserEmail", $preparedSteps[1]['type']);
        $this->assertSame("sign", $preparedSteps[1]['mode']);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCannotPrepareStampsStepsWithNoStepsExpectAnEmptyArray(): void
    {
        $steps = [];

        $preparedSteps = FastParapheurController::prepareStampsSteps($steps);

        $this->assertIsArray($preparedSteps);
        $this->assertEmpty($preparedSteps);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCannotPrepareStampsStepsWithNoSignaturePositionsInStepsExpectAnEmptyArray(): void
    {
        self::$signStep[0]['signaturePositions'] = [];

        $preparedSteps = FastParapheurController::prepareStampsSteps(self::$signStep);

        $this->assertIsArray($preparedSteps);
        $this->assertEmpty($preparedSteps);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCanOnlyPrepareStampsStepsWithOnlyOneSignaturePositionInStepExpectAnEmptyArray(): void
    {
        self::$signStep[0]['signaturePositions'][] = [
            "sequence"     => 0,
            "page"         => 1,
            "positionX"    => 30,
            "positionY"    => 100,
            "mainDocument" => false,
            "resId"        => 100
        ];

        $preparedSteps = FastParapheurController::prepareStampsSteps(self::$signStep);

        $this->assertIsArray($preparedSteps);
        $this->assertNotEmpty($preparedSteps);
        $this->assertArrayHasKey(100, $preparedSteps);
        $this->assertSame(1, count($preparedSteps[100]));
        $this->assertArrayHasKey('pictogramme-signature', $preparedSteps[100]);
        $this->assertSame(1, count($preparedSteps[100]['pictogramme-signature']));
        $this->assertSame(1, count($preparedSteps[100]['pictogramme-signature']));
        $this->assertArrayNotHasKey(0, $preparedSteps[100]['pictogramme-signature'][0]['position']);
        $this->assertSame(27, $preparedSteps[100]['pictogramme-signature'][0]['position']['x']);
        $this->assertSame(70, $preparedSteps[100]['pictogramme-signature'][0]['position']['y']);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCanPrepareSignStampsStepsWithSignaturePositionsInStepsExpectAnArray(): void
    {
        $preparedSteps = FastParapheurController::prepareStampsSteps(self::$signStep);

        $this->assertIsArray($preparedSteps);
        $this->assertNotEmpty($preparedSteps);
        $this->assertArrayHasKey(100, $preparedSteps); // signable document has a stamp position
        $this->assertSame(1, count($preparedSteps[100])); // signable document has only one type of stamp
        $this->assertArrayHasKey('pictogramme-signature', $preparedSteps[100]); // signable document has sign stamp
        // signable document has only one sign stamp
        $this->assertSame(1, count($preparedSteps[100]['pictogramme-signature']));
    }

    /**
     * @return void
     */
    public function testCanPrepareStampStepsWithTwoSignableDocumentsAndOneStampPositionExpectOneDocumentToHaveOneSignStampPosition(): void
    {
        self::$signStep[] = [
            "resId"                => 101,
            "mainDocument"         => false,
            "externalId"           => "user2@maarch.test",
            "sequence"             => 1,
            "action"               => "sign",
            "signatureMode"        => "sign",
            "signaturePositions"   => [],
            "datePositions"        => [],
            "externalInformations" => null
        ];

        $preparedSteps = FastParapheurController::prepareStampsSteps(self::$signStep);

        $this->assertIsArray($preparedSteps);
        $this->assertNotEmpty($preparedSteps);
        $this->assertArrayHasKey(100, $preparedSteps);
        $this->assertSame(1, count($preparedSteps[100]));
        $this->assertArrayHasKey('pictogramme-signature', $preparedSteps[100]);
        $this->assertSame(1, count($preparedSteps[100]['pictogramme-signature']));
    }

    /**
     * @return void
     */
    public function testCanGetVisaStampStepFromWorkflowWithUserStampPosition(): void
    {
        $preparedSteps = FastParapheurController::prepareStampsSteps(self::$visaStep);

        $this->assertIsArray($preparedSteps);
        $this->assertNotEmpty($preparedSteps);
        $this->assertArrayHasKey(100, $preparedSteps);
        $this->assertSame(1, count($preparedSteps[100]));
        $this->assertArrayHasKey('pictogramme-visa', $preparedSteps[100]);
        $this->assertSame(1, count($preparedSteps[100]['pictogramme-visa']));
    }

    /**
     * @return void
     */
    public function testCanPrepareStampStepsWithOtpStampPosition(): void
    {
        $step = self::$signStep;
        $step[] = [
            "resId"                => 100,
            "mainDocument"         => true,
            "sequence"             => 1,
            "action"               => "sign",
            "signatureMode"        => "sign",
            "signaturePositions"   => [
                [
                    "sequence"     => 1,
                    "page"         => 1,
                    "positionX"    => 10,
                    "positionY"    => 20,
                    "mainDocument" => false,
                    "resId"        => 100
                ]
            ],
            "datePositions"        => [],
            "externalInformations" => [
                "firstname"      => "Jenny",
                "lastname"       => "JANE",
                "email"          => "jjane@maarch.test",
                "phone"          => "+9900000000",
                "sourceId"       => 1,
                "type"           => "fast",
                "role"           => "sign",
                "availableRoles" => [
                    "sign"
                ]
            ]
        ];

        $preparedSteps = FastParapheurController::prepareStampsSteps($step);

        $this->assertIsArray($preparedSteps);
        $this->assertNotEmpty($preparedSteps);
        $this->assertArrayHasKey(100, $preparedSteps);
        $this->assertSame(1, count($preparedSteps[100]));
        $this->assertArrayHasKey('pictogramme-signature', $preparedSteps[100]);
        $this->assertSame(2, count($preparedSteps[100]['pictogramme-signature']));
        $this->assertSame(
            "Signé par: \${OTP_INFOS[firstname,lastname]}",
            $preparedSteps[100]['pictogramme-signature'][1]['bottom'][0]['value']
        );
    }

    /**
     * @return void
     */
    public function testCanGetVisaAndSignStampsStepsForTheSameDocument(): void
    {
        self::$signStep[0]['sequence'] = 1;
        self::$signStep[0]['signaturePositions'][0]['sequence'] = 1;

        $steps = [
            self::$visaStep[0],
            self::$signStep[0]
        ];

        $preparedSteps = FastParapheurController::prepareStampsSteps($steps);

        $this->assertIsArray($preparedSteps);
        $this->assertNotEmpty($preparedSteps);
        $this->assertArrayHasKey(100, $preparedSteps);
        $this->assertSame(2, count($preparedSteps[100]));
        $this->assertArrayHasKey('pictogramme-visa', $preparedSteps[100]);
        $this->assertSame(1, count($preparedSteps[100]['pictogramme-visa']));
        $this->assertArrayHasKey('pictogramme-signature', $preparedSteps[100]);
        $this->assertSame(1, count($preparedSteps[100]['pictogramme-signature']));
    }

    /**
     * @return void
     */
    public function testCannotGenerateXmlPictogrammeWithEmptyPictogrammeArrayInput(): void
    {
        $pictogrammes = [];

        $xml = FastParapheurController::generateXmlPictogramme($pictogrammes);

        $this->assertEmpty($xml);
    }

    /**
     * @return void
     */
    public function testGeneratesCorrectXmlOfPictograms(): void
    {
        $pictogrammes = [
            'pictogram-type' => [
                [
                    'index'     => '1',
                    'border'    => 'true',
                    'opacite'   => 'true',
                    'font-size' => '12',
                    'position'  => ['height' => '40', 'width' => '60', 'x' => '100', 'y' => '200', 'page' => 1],
                    'top'       => [['name' => 'info', 'value' => 'top side']],
                    'center'    => [['name' => 'info', 'value' => 'center']],
                    'bottom'    => [
                        ['name' => 'info-line1', 'value' => 'bottom side, line 1'],
                        ['name' => 'info-line2']
                    ],
                    'left'      => [['name' => 'info', 'value' => 'left side']],
                    'right'     => [['name' => 'info', 'value' => 'right side']]
                ]
            ]
        ];
        $expectedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $expectedXml .= '<pictogrammes><pictogram-type><pictogramme border="true" font-size="12" index="1" opacite="true">';
        $expectedXml .= '<position height="40" width="60" x="100" y="200" page="1"/><top><metadata name="info" value="top side"/></top>';
        $expectedXml .= '<center><metadata name="info" value="center"/></center>';
        $expectedXml .= '<bottom><metadata name="info-line1" value="bottom side, line 1"/><metadata name="info-line2"/></bottom>';
        $expectedXml .= '<left><metadata name="info" value="left side"/></left>';
        $expectedXml .= '<right><metadata name="info" value="right side"/></right>';
        $expectedXml .= '</pictogramme></pictogram-type></pictogrammes>';

        $result = FastParapheurController::generateXmlPictogramme($pictogrammes);

        $this->assertXmlStringEqualsXmlString($expectedXml, $result);
    }
}
