<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\resource\Application;

use MaarchCourrier\Core\Domain\Problem\ParameterMustBeGreaterThanZeroException;
use MaarchCourrier\Tests\app\resource\Mock\ResourceDataMock;
use MaarchCourrier\Tests\app\resource\Mock\ResourceFileMock;
use PHPUnit\Framework\TestCase;
use Resource\Application\RetrieveDocserverAndFilePath;
use Resource\Application\RetrieveResource;
use Resource\Domain\Exceptions\ConvertedResultException;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceFingerPrintDoesNotMatchException;
use Resource\Domain\Exceptions\ResourceHasNoFileException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;

class RetrieveResourceTest extends TestCase
{
    private ResourceDataMock $resourceDataMock;
    private ResourceFileMock $resourceFileMock;
    private RetrieveResource $retrieveResource;

    protected function setUp(): void
    {
        $this->resourceDataMock = new ResourceDataMock();
        $this->resourceFileMock = new ResourceFileMock();

        $this->retrieveResource = new RetrieveResource(
            $this->resourceDataMock,
            $this->resourceFileMock,
            new RetrieveDocserverAndFilePath($this->resourceDataMock, $this->resourceFileMock)
        );
    }

    /**
     * @return void
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     */
    public function testCannotGetMainFileBecauseResourceDoesNotExist(): void
    {
        $this->resourceDataMock->doesResourceExist = false;

        $this->expectExceptionObject(new ResourceDoesNotExistException());

        $this->retrieveResource->getResourceFile(1, true);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetMainFileBecauseResourceHasNoFileReferenceInDatabase(): void
    {
        $this->resourceDataMock->doesResourceFileExistInDatabase = false;

        $this->expectExceptionObject(new ResourceHasNoFileException());

        $this->retrieveResource->getResourceFile(1, true);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetMainFileBecauseResourceUnknownDocserverReferenceInDatabase(): void
    {
        $this->resourceDataMock->doesResourceDocserverExist = false;

        $this->expectExceptionObject(new ResourceDocserverDoesNotExistException());

        $this->retrieveResource->getResourceFile(1, true);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetMainFileBecauseResourceFileDoesNotExistInDocserver(): void
    {
        $this->resourceFileMock->doesFileExist = false;

        $this->expectExceptionObject(new ResourceNotFoundInDocserverException());

        $this->retrieveResource->getResourceFile(1, true);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetMainFileBecauseResourceFingerprintDoesNotMatch(): void
    {
        $this->resourceFileMock->documentFingerprint = 'other fingerprint';

        $this->expectExceptionObject(new ResourceFingerPrintDoesNotMatchException());

        $this->retrieveResource->getResourceFile(1, true);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetMainFileBecauseResourceFailedToGetContentFromDocserver(): void
    {
        $this->resourceFileMock->doesWatermarkInResourceFileContentFail = true;
        $this->resourceFileMock->doesResourceFileGetContentFail = true;

        $this->expectExceptionObject(new ResourceFailedToGetDocumentFromDocserverException());

        $this->retrieveResource->getResourceFile(1, true);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetResourceFileWithoutWatermarkBecauseAppliedWatermarkFailed(): void
    {
        $this->resourceFileMock->returnResourceThumbnailFileContent = false;
        $this->resourceFileMock->doesWatermarkInResourceFileContentFail = true;

        $result = $this->retrieveResource->getResourceFile(1, false);

        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertNotEmpty($result->getOriginalFormat());
        $this->assertSame($result->getFormatFilename(), 'Maarch Courrier Test');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->mainResourceFileContent);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetResourceFileWithWatermarkApplied(): void
    {
        $this->resourceFileMock->returnResourceThumbnailFileContent = false;

        $result = $this->retrieveResource->getResourceFile(1, true);

        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertNotEmpty($result->getOriginalFormat());
        $this->assertSame($result->getFormatFilename(), 'Maarch Courrier Test');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->mainWatermarkInResourceFileContent);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testWhenTheResourceIsRetrievedAndTheResIdIsLessThanOneAnExceptionIsReturned(): void
    {
        $this->expectException(ParameterMustBeGreaterThanZeroException::class);

        $this->retrieveResource->getResourceFile(0, true);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetResourceFileIsValidWhenTheFingerprintIsCorrectlyUpdated(): void
    {
        $this->resourceDataMock->fingerprint = '';

        $this->retrieveResource->getResourceFile(1, false);
        $this->assertTrue($this->resourceDataMock->doesFingerprint);
    }

    /**
     * @return void
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testWhenAnExceptionIsReturnedWhenTheConversionOfTheResourceToPdfHasFailed(): void
    {
        $this->expectException(ConvertedResultException::class);

        $this->resourceDataMock->convertedPdfByIdHasFailed = true;

        $this->retrieveResource->getResourceFile(1, false);
    }
}
