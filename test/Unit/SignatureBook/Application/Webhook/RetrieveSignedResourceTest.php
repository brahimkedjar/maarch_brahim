<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   RetrieveSignedResource test
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Application\Webhook;

use MaarchCourrier\SignatureBook\Application\Webhook\RetrieveSignedResource;
use MaarchCourrier\SignatureBook\Domain\Problem\NoEncodedContentRetrievedProblem;
use MaarchCourrier\SignatureBook\Domain\SignedResource;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\CurrentUserInformationsMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action\MaarchParapheurSignatureServiceMock;
use PHPUnit\Framework\TestCase;

class RetrieveSignedResourceTest extends TestCase
{
    private RetrieveSignedResource $retrieveSignedResource;
    private MaarchParapheurSignatureServiceMock $maarchParapheurSignatureService;
    private SignedResource $signedResource;
    private string $retrieveDocUri = "http://10.1.5.12/maarch-parapheur-api/rest/documents/11/content?mode=base64&type=esign";

    protected function setUp(): void
    {
        $currentUserRepositoryMock = new CurrentUserInformationsMock();
        $this->maarchParapheurSignatureService = new MaarchParapheurSignatureServiceMock();

        $this->retrieveSignedResource = new RetrieveSignedResource(
            $currentUserRepositoryMock,
            $this->maarchParapheurSignatureService
        );

        $this->signedResource = new SignedResource();

        $this->signedResource->setResIdSigned(10);
        $this->signedResource->setResIdMaster(100);
        $this->signedResource->setStatus('VAL');
    }

    protected function tearDown(): void
    {
        $this->signedResource->setResIdSigned(10);
        $this->signedResource->setResIdMaster(100);
        $this->signedResource->setStatus('VAL');
    }

    /**
     * @throws NoEncodedContentRetrievedProblem
     */
    public function testCanRetrieveSignedResourceIfEncodedContentIsSet(): void
    {
        $signedResource = $this->retrieveSignedResource->retrieveSignedResourceContent(
            $this->signedResource,
            $this->retrieveDocUri
        );
        $this->assertSame($signedResource->getResIdSigned(), $this->signedResource->getResIdSigned());
        $this->assertSame($signedResource->getResIdMaster(), $this->signedResource->getResIdMaster());
        $this->assertNotNull($signedResource->getEncodedContent());
    }

    public function testCannotRetrieveSignedResourceIfEncodedContentIsEmpty(): void
    {
        $this->maarchParapheurSignatureService->returnFromParapheur['encodedDocument'] = null;
        $this->expectException(NoEncodedContentRetrievedProblem::class);
        $this->retrieveSignedResource->retrieveSignedResourceContent($this->signedResource, $this->retrieveDocUri);
    }
}
