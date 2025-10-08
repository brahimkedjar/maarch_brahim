<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\attachment;

use Attachment\controllers\AttachmentController;
use MaarchCourrier\Tests\CourrierTestCase;
use SrcCore\http\Response;

class AttachmentVersionControllerTest extends CourrierTestCase
{
    public function testCanAddANewVersionAndOnlyAccessToTheVersion()
    {
        $attachmentController = new AttachmentController();

        // ARRANGE
        /*
         * - Ajout d'une PJ
         */

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $args = [
            'title'        => 'Nouvelle PJ de test',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA'
        ];

        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $originalAttachmentId = $responseBody->id;

        // ACT : Ajout de la version
        $args = [
            'title'        => 'Nouvelle PJ de test - v2',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA',
            'originId'     => $originalAttachmentId
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $versionAttachmentId = $responseBody->id;

        // ASSERT 2
        $this->assertIsInt($versionAttachmentId);

        // Attendu : La PJ d'origine ne doit pas être accessible
        $request = $this->createRequest('GET');
        $response = $attachmentController->getById($request, new Response(), ['id' => $originalAttachmentId]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Attachment does not exist', $responseBody->errors);

        // Attendu : La version créée doit être accessible et doit être liée à la PJ d'origine
        $request = $this->createRequest('GET');
        $response = $attachmentController->getById($request, new Response(), ['id' => $versionAttachmentId]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('A_TRA', $responseBody->status);
        $this->assertSame($originalAttachmentId, $responseBody->versions[0]->resId);
    }

    public function testCannotAddANewVersionFromAnAttachmentThatIsAlreadyAVersion()
    {
        $attachmentController = new AttachmentController();

        // ARRANGE
        /*
         * - Ajout d'une PJ
         * - Ajout d'une version
         */
        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $args = [
            'title'        => 'Nouvelle PJ de test',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA'
        ];

        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $originalAttachmentId = $responseBody->id;

        $args = [
            'title'        => 'Nouvelle PJ de test',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA',
            'originId'     => $originalAttachmentId
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $versionAttachmentId = $responseBody->id;

        // ACT : Ajout de la version à partir de la version
        $args = [
            'title'        => 'Nouvelle PJ de test - V2',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA',
            'originId'     => $versionAttachmentId
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody(), true);

        // ASSERT
        // Attendu : Impossible de rajouter une nouvelle version à une PJ qui est déjà une version d'une autre
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Body originId can not be a version, it must be the original version', $responseBody['errors']);
    }

    public function testCannotAddANewVersionFromASignedAttachment()
    {
        $attachmentController = new AttachmentController();

        // ARRANGE
        /*
         * - Ajout d'une PJ
         * - Ajout d'une réponse signée à partir de la PJ
         */
        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $args = [
            'title'        => 'Nouvelle PJ de test',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA'
        ];

        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $originalAttachmentId = $responseBody->id;

        $args = [
            'title'        => 'Nouvelle PJ signée',
            'type'         => 'signed_response',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'TRA',
            'originId'     => $originalAttachmentId
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $attachmentController->create($fullRequest, new Response());

        // ACT : Ajout de la version à partir de la réponse signée
        $args = [
            'title'        => 'Nouvelle PJ - v2',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA',
            'originId'     => $originalAttachmentId
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody(),true);

        // ASSERT
        // Attendu : Impossible de rajouter une nouvelle version à une PJ dont le statut est signé (SIGN)
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame("Body originId has not an authorized status. Origin status is either 'SIGN' or 'FRZ'", $responseBody['errors']);
    }

    public function testCanAddNewSignedPjToAnAttachmentWhichIsAVersion()
    {
        $attachmentController = new AttachmentController();

        // ARRANGE
        /*
         * - Ajout d'une PJ
         * - Ajout d'une version
         */
        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $args = [
            'title'        => 'Nouvelle PJ de test',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA'
        ];

        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $originalAttachmentId = $responseBody->id;

        $args = [
            'title'        => 'Nouvelle PJ de test - v2',
            'type'         => 'response_project',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'A_TRA',
            'originId'     => $originalAttachmentId
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $versionAttachmentId = $responseBody->id;

        // ACT : Ajout d'une réponse signée à une PJ ayant une version
        $args = [
            'title'        => 'Nouvelle PJ signée',
            'type'         => 'signed_response',
            'resIdMaster'  => 100,
            'encodedFile'  => $encodedFile,
            'recipientId'  => 19,
            'recipientType'=> 'user',
            'format'       => 'txt',
            'status'       => 'TRA',
            'originId'     => $originalAttachmentId
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $attachmentController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $signedVersionAttachmentId = $responseBody->id;

        // ASSERT
        $this->assertIsInt($signedVersionAttachmentId);

        // Attendu : La Pièce originelle doit être complètement inaccessible
        $request = $this->createRequest('GET');
        $response = $attachmentController->getById($request, new Response(), ['id' => $originalAttachmentId]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Attachment does not exist', $responseBody->errors);

        // Attendu :
        // - La version doit être passée au status SIGN
        // - Elle doit avoir la réponse signée attachée correspondante à celle qui a été créée précédemment
        $request = $this->createRequest('GET');
        $response = $attachmentController->getById($request, new Response(), ['id' => $versionAttachmentId]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('SIGN', $responseBody->status);
        $this->assertSame($signedVersionAttachmentId, $responseBody->signedResponse);

        /// Attendu :
        // - La réponse signée doit être au statut TRA
        // - Elle ne doit pas être une version d'une autre PJ
        $request = $this->createRequest('GET');
        $response = $attachmentController->getById($request, new Response(), ['id' => $signedVersionAttachmentId]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('TRA', $responseBody->status);
        $this->assertEmpty($responseBody->versions);
    }
}
