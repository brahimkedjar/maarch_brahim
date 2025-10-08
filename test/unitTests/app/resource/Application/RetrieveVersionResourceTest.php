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
use Resource\Application\RetrieveVersionResource;
use Resource\Domain\Exceptions\ParameterCanNotBeEmptyException;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceFingerPrintDoesNotMatchException;
use Resource\Domain\Exceptions\ResourceHasNoFileException;
use Resource\Domain\Exceptions\ResourceIncorrectVersionException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;

class RetrieveVersionResourceTest extends TestCase
{
    private ResourceDataMock $resourceDataMock;
    private ResourceFileMock $resourceFileMock;
    private RetrieveVersionResource $retrieveVersionResource;

    protected function setUp(): void
    {
        $this->resourceDataMock = new ResourceDataMock();
        $this->resourceFileMock = new ResourceFileMock();

        $this->retrieveVersionResource = new RetrieveVersionResource(
            $this->resourceDataMock,
            $this->resourceFileMock,
            new RetrieveDocserverAndFilePath($this->resourceDataMock, $this->resourceFileMock)
        );
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     * @throws ResourceIncorrectVersionException
     */
    public function testCannotGetVersionResourceFileBecauseResIdNotValidParam(): void
    {
        // Arrange

        // Assert
        $this->expectExceptionObject(new ParameterMustBeGreaterThanZeroException('resId'));

        // Act
        $this->retrieveVersionResource->getResourceFile(0, 0, '');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseVersionNotValidParam(): void
    {
        // Arrange

        // Assert
        $this->expectExceptionObject(new ParameterMustBeGreaterThanZeroException('version'));

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 0, '');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseTypeIsEmpty(): void
    {
        // Arrange

        // Assert
        $this->expectExceptionObject(new ParameterCanNotBeEmptyException('type', implode(', ', $this->resourceDataMock::ADR_RESOURCE_TYPES)));

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, '');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseTypeNotValidParam(): void
    {
        // Arrange

        // Assert
        $this->expectExceptionObject(new ParameterCanNotBeEmptyException('type', implode(', ', $this->resourceDataMock::ADR_RESOURCE_TYPES)));

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, 'TNLL');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseResourceDoesNotExist(): void
    {
        // Arrange
        $this->resourceDataMock->doesResourceExist = false;

        // Assert
        $this->expectExceptionObject(new ResourceDoesNotExistException());

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseResourceHasNoFileReferenceInDatabase(): void
    {
        // Arrange
        $this->resourceDataMock->doesResourceFileExistInDatabase = false;

        // Assert
        $this->expectExceptionObject(new ResourceHasNoFileException());

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseResourceUnknownDocserverReferenceInDatabase(): void
    {
        // Arrange
        $this->resourceDataMock->doesResourceDocserverExist = false;

        // Assert
        $this->expectExceptionObject(new ResourceDocserverDoesNotExistException());

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseResourceFileDoesNotExistInDocserver(): void
    {
        // Arrange
        $this->resourceFileMock->doesFileExist = false;

        // Assert
        $this->expectExceptionObject(new ResourceNotFoundInDocserverException());

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseResourceFingerprintDoesNotMatch(): void
    {
        // Arrange
        $this->resourceFileMock->documentFingerprint = 'other fingerprint';

        // Assert
        $this->expectExceptionObject(new ResourceFingerPrintDoesNotMatchException());

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetVersionResourceFileBecauseResourceFailedToGetContentFromDocserver(): void
    {
        // Arrange
        $this->resourceFileMock->doesWatermarkInResourceFileContentFail =true;
        $this->resourceFileMock->doesResourceFileGetContentFail = true;

        // Assert
        $this->expectExceptionObject(new ResourceFailedToGetDocumentFromDocserverException());

        // Act
        $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetVersionResourceFileWithoutWatermarkBecauseAppliedWatermarkFailed(): void
    {
        // Arrange
        $this->resourceFileMock->returnResourceThumbnailFileContent = false;
        $this->resourceFileMock->doesWatermarkInResourceFileContentFail = true;

        // Act
        $result = $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');

        // Assert
        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertNotEmpty($result->getOriginalFormat());
        $this->assertSame($result->getFormatFilename(), 'Maarch Courrier Test');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->mainResourceFileContent);
    }

    /**
     * @return void
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceIncorrectVersionException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetVersionResourceFileWithWatermarkApplied(): void
    {
        // Arrange
        $this->resourceFileMock->returnResourceThumbnailFileContent = false;

        // Act
        $result = $this->retrieveVersionResource->getResourceFile(1, 1, 'PDF');

        // Assert
        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertNotEmpty($result->getOriginalFormat());
        $this->assertSame($result->getFormatFilename(), 'Maarch Courrier Test');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->mainWatermarkInResourceFileContent);
    }
}
