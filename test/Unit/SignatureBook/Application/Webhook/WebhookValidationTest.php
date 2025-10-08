<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   WebhookValidation test
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Application\Webhook;

use MaarchCourrier\SignatureBook\Application\Webhook\WebhookValidation;
use MaarchCourrier\SignatureBook\Domain\Problem\AttachmentOutOfPerimeterProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\IdParapheurIsMissingProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceAlreadySignProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ResourceIdMasterNotCorrespondingProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\RetrieveDocumentUrlEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\SignedResource;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\CurrentUserInformationsMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\UserRepositoryMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Webhook\ResourceToSignRepositoryMock;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use PHPUnit\Framework\TestCase;

class WebhookValidationTest extends TestCase
{
    private ResourceToSignRepositoryMock $resourceToSignRepositoryMock;
    private WebhookValidation $webhookValidation;
    private UserRepositoryMock $userRepositoryMock;
    private array $bodySentByMP = [
        'identifier'     => 'TDy3w2zAOM41M216',
        'signatureState' => [
            'error'       => '',
            'state'       => 'VAL',
            'message'     => '',
            'updatedDate' => "2024-03-01T13:19:59+01:00"
        ],
        'payload'        => [
            'idParapheur' => 30
        ],
        'token'          => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyZXNfaWQi
                             OjE1OSwidXNlcklkIjoxMH0.olM35fZrHlsYXTRceohEqijjIOqCNolVSbw0v5eKW78',
        'retrieveDocUri' => "http://10.1.5.12/maarch-parapheur-api/rest/documents/11/content?mode=base64&type=esign"
    ];

    private array $decodedToken = [
        'resId'        => 159,
        'userSerialId' => 10
    ];


    protected function setUp(): void
    {
        $this->resourceToSignRepositoryMock = new ResourceToSignRepositoryMock();
        $this->userRepositoryMock = new UserRepositoryMock();
        $currentUserInformationsMock = new CurrentUserInformationsMock();

        $this->webhookValidation = new WebhookValidation(
            $this->resourceToSignRepositoryMock,
            $this->userRepositoryMock,
            $currentUserInformationsMock
        );
    }

    protected function tearDown(): void
    {
        $this->bodySentByMP = [
            'identifier'     => 'TDy3w2zAOM41M216',
            'signatureState' => [
                'error'       => '',
                'state'       => 'VAL',
                'message'     => '',
                'updatedDate' => null
            ],
            'payload'        => [
                'idParapheur' => 30
            ],
            'token'          => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyZXNfaWQiOjE1O
                                 SwidXNlcklkIjoxMH0.olM35fZrHlsYXTRceohEqijjIOqCNolVSbw0v5eKW78',
            'retrieveDocUri' => "http://10.1.5.12/maarch-parapheur-api/rest/documents/11/content?mode=base64&type=esign"
        ];

        $this->resourceToSignRepositoryMock->attachmentNotExists = false;
        $this->resourceToSignRepositoryMock->resourceAlreadySigned = false;
        $this->resourceToSignRepositoryMock->resIdConcordingWithResIdMaster = true;
    }


    /**
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws UserDoesNotExistProblem
     */
    public function testValidationSuccessIfAllParametersAreSetAndValid(): void
    {
        $this->decodedToken = [
            'resId'        => 159,
            'resIdMaster'  => 75,
            'userSerialId' => 10
        ];
        $signedResource = $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
        $this->assertInstanceOf(SignedResource::class, $signedResource);
        $this->assertSame($signedResource->getResIdSigned(), $this->decodedToken['resId']);
        $this->assertSame($signedResource->getResIdMaster(), $this->decodedToken['resIdMaster']);
        $this->assertSame($signedResource->getStatus(), $this->bodySentByMP['signatureState']['state']);
    }

    /**
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws UserDoesNotExistProblem
     */
    public function testValidationErrorIfRetrieveUrlIsEmpty(): void
    {
        $this->bodySentByMP['retrieveDocUri'] = '';
        $this->expectException(RetrieveDocumentUrlEmptyProblem::class);
        $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
    }

    /**
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws UserDoesNotExistProblem
     */
    public function testValidationErrorIfResIdIsMissing(): void
    {
        unset($this->decodedToken['resId']);
        $this->expectException(ResourceIdEmptyProblem::class);
        $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
    }

    /**
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws UserDoesNotExistProblem
     */
    public function testValidationErrorIfResIdNotCorrespondingToResIdMaster(): void
    {
        $this->decodedToken = [
            'resId'        => 159,
            'resIdMaster'  => 75,
            'userSerialId' => 10
        ];

        $this->resourceToSignRepositoryMock->resIdConcordingWithResIdMaster = false;

        $this->expectException(ResourceIdMasterNotCorrespondingProblem::class);
        $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
    }

    /**
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws UserDoesNotExistProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     */
    public function testValidationErrorIfAttachmentNotInPerimeter(): void
    {
        $this->decodedToken = [
            'resId'        => 159,
            'resIdMaster'  => 75,
            'userSerialId' => 10
        ];

        $this->resourceToSignRepositoryMock->attachmentNotExists = true;

        $this->expectException(AttachmentOutOfPerimeterProblem::class);
        $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
    }

    /**
     * @throws AttachmentOutOfPerimeterProblem
     * @throws CurrentTokenIsNotFoundProblem
     * @throws ResourceIdEmptyProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     */
    public function testValidationErrorIfUserNotExists(): void
    {
        $this->userRepositoryMock->doesUserExist = false;
        $this->expectException(UserDoesNotExistProblem::class);
        $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
    }

    /**
     * @throws AttachmentOutOfPerimeterProblem
     * @throws ResourceIdEmptyProblem
     * @throws ResourceIdMasterNotCorrespondingProblem
     * @throws UserDoesNotExistProblem
     * @throws RetrieveDocumentUrlEmptyProblem
     */
    public function testValidationErrorIfTokenIsNotSet(): void
    {
        unset($this->bodySentByMP['token']);
        $this->expectException(CurrentTokenIsNotFoundProblem::class);
        $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
    }

    public function testValidationErrorIfIdParapheurIsNotSet(): void
    {
        unset($this->bodySentByMP['payload']['idParapheur']);
        $this->expectException(IdParapheurIsMissingProblem::class);
        $this->webhookValidation->validateAndCreateResource($this->bodySentByMP, $this->decodedToken);
    }
}
