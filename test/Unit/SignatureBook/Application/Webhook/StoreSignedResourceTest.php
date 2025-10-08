<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   StoreSignedResource test
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Application\Webhook;

use MaarchCourrier\SignatureBook\Application\Webhook\StoreSignedResource;
use MaarchCourrier\SignatureBook\Domain\Problem\StoreResourceProblem;
use MaarchCourrier\SignatureBook\Domain\SignedResource;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Webhook\ResourceToSignRepositoryMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Webhook\StoreSignedResourceServiceMock;
use PHPUnit\Framework\TestCase;

class StoreSignedResourceTest extends TestCase
{
    private ResourceToSignRepositoryMock $resourceToSignRepositoryMock;
    private StoreSignedResourceServiceMock $storeSignedResourceServiceMock;
    private StoreSignedResource $storeSignedResource;
    private array $returnFromCurlRequestParapheur = [];

    protected function setUp(): void
    {
        $this->resourceToSignRepositoryMock = new ResourceToSignRepositoryMock();
        $this->storeSignedResourceServiceMock = new StoreSignedResourceServiceMock();

        $this->storeSignedResource = new StoreSignedResource(
            $this->resourceToSignRepositoryMock,
            $this->storeSignedResourceServiceMock
        );

        $this->returnFromCurlRequestParapheur = [
            'encodedDocument' => 'ContenuDunNouveauFichier',
            'mimetype'        => "application/pdf",
            'filename'        => "PDF_signature.pdf"
        ];
    }

    /**
     * @throws StoreResourceProblem
     */
    public function testCanStoreSignedVersionOfResource(): void
    {
        $signedResource = new SignedResource();
        $signedResource->setResIdSigned(100);
        $signedResource->setResIdMaster(null);
        $signedResource->setStatus("VAL");
        $signedResource->setEncodedContent($this->returnFromCurlRequestParapheur['encodedDocument']);

        $newId = $this->storeSignedResource->store($signedResource);
        $this->assertSame($newId, $signedResource->getResIdSigned());
        $this->assertTrue($this->resourceToSignRepositoryMock->signedVersionCreate);
    }

    /**
     * @throws StoreResourceProblem
     */
    public function testCannotStoreSignedVersionOfResourceIfStorageFunctionError(): void
    {
        $this->storeSignedResourceServiceMock->errorStorage = true;

        $signedResource = new SignedResource();
        $signedResource->setResIdSigned(10);
        $signedResource->setResIdMaster(null);
        $signedResource->setStatus("VAL");
        $signedResource->setEncodedContent($this->returnFromCurlRequestParapheur['encodedDocument']);

        $this->expectException(StoreResourceProblem::class);
        $newId = $this->storeSignedResource->store($signedResource);
    }

    /**
     * @throws StoreResourceProblem
     */
    public function testCanStoreSignedVersionOfAttachment(): void
    {
        $this->storeSignedResourceServiceMock->resIdNewSignedDoc = 1;

        $signedResource = new SignedResource();
        $signedResource->setResIdSigned(100);
        $signedResource->setResIdMaster(10);
        $signedResource->setStatus("VAL");
        $signedResource->setEncodedContent($this->returnFromCurlRequestParapheur['encodedDocument']);

        $newId = $this->storeSignedResource->store($signedResource);
        $this->assertSame($newId, $this->storeSignedResourceServiceMock->resIdNewSignedDoc);
        $this->assertTrue($this->resourceToSignRepositoryMock->attachmentUpdated);
    }
}
