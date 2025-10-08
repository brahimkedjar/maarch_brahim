<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Signature Book Controller Test
 * @author dev@maarch.org
 */

namespace Functional\SignatureBook\Infrastructure\Controller;

use Attachment\controllers\AttachmentController;
use Attachment\models\AttachmentModel;
use Entity\controllers\ListInstanceController;
use Entity\models\ListInstanceModel;
use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\SignatureBook\Infrastructure\Controller\RetrieveSignatureBookController;
use MaarchCourrier\Tests\CourrierTestCase;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use SrcCore\http\Response;

class RetrieveSignatureBookControllerTest extends CourrierTestCase
{
    private int $connectedUser;
    private ?int $mainResourceId;
    private ?array $attachmentIds = [];

    protected function setUp(): void
    {
        $this->connectAsUser('mmanfred');
        $this->connectedUser = $GLOBALS['id'];
    }

    protected function tearDown(): void
    {
        if (!empty($this->mainResourceId)) {
            ResModel::delete(['where' => ['res_id = ?'], 'data' => [$this->mainResourceId]]);
        }
        if (!empty($this->attachmentIds)) {
            AttachmentModel::delete(['where' => ['res_id in (?)'], 'data' => [$this->attachmentIds]]);
        }
    }

    private function createMainResource(string $encodedFileContent): void
    {
        $body = [
            'modelId'          => 1,
            'status'           => 'NEW',
            'encodedFile'      => $encodedFileContent,
            'format'           => 'txt',
            'confidentiality'  => false,
            'chrono'           => true,
            'documentDate'     => '2019-01-01 17:18:47',
            'arrivalDate'      => '2019-01-01 17:18:47',
            'processLimitDate' => '2029-01-01',
            'doctype'          => 103,
            'destination'      => 4,
            'subject'          => 'Breaking News : Superman is alive - PHP unit',
            'typist'           => $this->connectedUser,
            'priority'         => 'poiuytre1357nbvc',
            'senders'          => [['type' => 'user', 'id' => 19]],
            'integrations'     => ['inSignatureBook' => true]
        ];
        $fullRequest = $this->createRequestWithBody('POST', $body);

        $resController = new ResController();
        $response = $resController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->mainResourceId = (int)$responseBody->resId;
    }

    private function createAttachments(int $resIdMaster, string $encodedFileContent): void
    {
        //Signable and in signature book
        $body = [
            'title'         => 'Breaking News : Superman is alive - PHP unit',
            'type'          => 'response_project',
            'chrono'        => 'MAARCH/2024A/24',
            'resIdMaster'   => $resIdMaster,
            'encodedFile'   => $encodedFileContent,
            'format'        => 'txt',
            'typist'        => $this->connectedUser,
            'inSignatureBook'  => true
        ];

        $fullRequest = $this->createRequestWithBody('POST', $body);

        $attachmentController = new AttachmentController();
        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->attachmentIds[] = $responseBody->id;

        //Not signable and in signature book
        $body['type'] = 'simple_attachment';
        $body['chrono'] = 'MAARCH/2024A/25';
        $fullRequest = $this->createRequestWithBody('POST', $body);

        $attachmentController = new AttachmentController();
        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->attachmentIds[] = $responseBody->id;

        //Signable and not in signature book
        $body['type'] = 'simple_attachment';
        $body['chrono'] = 'MAARCH/2024A/26';
        $body['inSignatureBook'] = false;
        $fullRequest = $this->createRequestWithBody('POST', $body);

        $attachmentController = new AttachmentController();
        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->attachmentIds[] = $responseBody->id;
    }

    private function createVisaCircuitForMainResource(int $resId): void
    {
        $body = [
            "resources" => [
                [
                    "resId" => $resId,
                    "listInstances" => [
                        [
                            "item_id"               => $this->connectedUser,
                            "item_type"             => "user",
                            "externalId"            => null,
                            "difflist_type"         => "VISA_CIRCUIT",
                            "signatory"             => false,
                            "requested_signature"   => true,
                            "hasPrivilege"          => true,
                            "isValid"               => true,
                            "currentRole"           => "sign"
                        ]
                    ]
                ]
            ]
        ];
        $fullRequest = $this->createRequestWithBody('PUT', $body);

        $listInstanceController = new ListInstanceController();
        $listInstanceController->updateCircuits($fullRequest, new Response(), ['type' => 'visaCircuit']);
    }

    private function sendMainResourceToInternalSignatureBook(int $mainResourceId): void
    {
        ResModel::update([
            'set'   => ['status' => 'ESIG'],
            'where' => ['res_id = ?'],
            'data'  => [$mainResourceId]
        ]);
    }

    /**
     * @throws ResourceDoesNotExistProblem
     * @throws MainResourceOutOfPerimeterProblem
     */
    public function testGetSignatureBookResourcesWhenNoErrorsOccurred(): void
    {
        //Arrange
        $fileContent = file_get_contents('test/Functional/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        //create main document
        $this->createMainResource($encodedFile);
        $mainResourceId = $this->mainResourceId;

            //create attachments
        $this->createAttachments($mainResourceId, $encodedFile);

        //create workflow visa for resource
        $this->createVisaCircuitForMainResource($mainResourceId);

        //send main resource to internal signature book
        $this->sendMainResourceToInternalSignatureBook($mainResourceId);

        $args = [
            'userId'    => $this->connectedUser,
            'groupId'   => 4,
            'basketId'  => 16,
            'resId'     => $mainResourceId
        ];
        $fullRequest = $this->createRequestWithBody('GET', $args);

        //Act
        $retrieveSignatureBookController = new RetrieveSignatureBookController();
        $response = $retrieveSignatureBookController->getSignatureBook($fullRequest, new Response(), $args);
        $responseBody = json_decode((string)$response->getBody());

        //Assert
        $this->assertNotEmpty($responseBody->resourcesToSign);
        $this->assertSame(2, count($responseBody->resourcesToSign));
        $this->assertSame(100, $responseBody->resourcesToSign[0]->resId);
        $this->assertSame('main_document', $responseBody->resourcesToSign[0]->type);
        $this->assertSame(1, $responseBody->resourcesToSign[1]->resId);
        $this->assertSame('response_project', $responseBody->resourcesToSign[1]->type);

        $this->assertNotEmpty($responseBody->resourcesAttached);
        $this->assertSame(1, count($responseBody->resourcesAttached));
        $this->assertSame(2, $responseBody->resourcesAttached[0]->resId);
        $this->assertSame('simple_attachment', $responseBody->resourcesAttached[0]->type);
    }
}
