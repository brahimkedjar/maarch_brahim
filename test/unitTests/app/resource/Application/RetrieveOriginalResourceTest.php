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
use Resource\Application\RetrieveOriginalResource;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceFingerPrintDoesNotMatchException;
use Resource\Domain\Exceptions\ResourceHasNoFileException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;
use Resource\Domain\ResourceFileInfo;

class RetrieveOriginalResourceTest extends TestCase
{
    private ResourceDataMock $resourceDataMock;
    private ResourceFileMock $resourceFileMock;
    private RetrieveOriginalResource $retrieveOriginalResource;

    protected function setUp(): void
    {
        $this->resourceDataMock = new ResourceDataMock();
        $this->resourceFileMock = new ResourceFileMock();

        $this->retrieveOriginalResource = new RetrieveOriginalResource(
            $this->resourceDataMock,
            $this->resourceFileMock,
            new RetrieveDocserverAndFilePath($this->resourceDataMock, $this->resourceFileMock)
        );
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetOriginalResourceFileBecauseResourceDoesNotExist(): void
    {
        // Arrange
        $this->resourceDataMock->doesResourceExist = false;

        // Assert
        $this->expectExceptionObject(new ResourceDoesNotExistException());

        // Act
        $this->retrieveOriginalResource->getResourceFile(1);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetOriginalResourceFileBecauseResourceHasNoFileReferenceInDatabase(): void
    {
        // Arrange
        $this->resourceDataMock->doesResourceFileExistInDatabase = false;

        // Assert
        $this->expectExceptionObject(new ResourceHasNoFileException());

        // Act
        $this->retrieveOriginalResource->getResourceFile(1);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetOriginalResourceFileBecauseResourceHasUnknownDocserverReferenceInDatabase(): void
    {
        // Arrange
        $this->resourceDataMock->doesResourceDocserverExist = false;

        // Assert
        $this->expectExceptionObject(new ResourceDocserverDoesNotExistException());

        // Act
        $this->retrieveOriginalResource->getResourceFile(1);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetOriginalResourceFileBecauseResourceFileDoesNotExistInDocserver(): void
    {
        // Arrange
        $this->resourceFileMock->doesFileExist = false;

        // Assert
        $this->expectExceptionObject(new ResourceNotFoundInDocserverException());

        // Act
        $this->retrieveOriginalResource->getResourceFile(1);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetOriginalResourceFileBecauseResourceFingerprintDoesNotMatch(): void
    {
        $this->resourceFileMock->documentFingerprint = 'other fingerprint';

        $this->expectExceptionObject(new ResourceFingerPrintDoesNotMatchException());

        $this->retrieveOriginalResource->getResourceFile(1);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetOriginalResourceFileBecauseResourceFailedToGetContentFromDocserver(): void
    {
        $this->resourceFileMock->doesResourceFileGetContentFail = true;

        $this->expectExceptionObject(new ResourceFailedToGetDocumentFromDocserverException());

        $this->retrieveOriginalResource->getResourceFile(1);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetOriginalResourceFile(): void
    {
        $this->resourceFileMock->returnResourceThumbnailFileContent = false;

        $result = $this->retrieveOriginalResource->getResourceFile(1);

        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertNotEmpty($result->getOriginalFormat());
        $this->assertSame($result->getFormatFilename(), 'Maarch Courrier Test');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->mainResourceFileContent);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetResourceFileReturnAnExceptionWhenTheParameterIsInferiorToOne(): void
    {

        $this->expectException(ParameterMustBeGreaterThanZeroException::class);

        $this->retrieveOriginalResource->getResourceFile(0, true);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetOriginalResourceFileIsValidWhenTheFingerprintIsCorrectlyUpdated(): void
    {
        $this->resourceDataMock->fingerprint = '';

        $this->retrieveOriginalResource->getResourceFile(1, false);
        $this->assertTrue($this->resourceDataMock->doesFingerprint);
    }

    /**
     * @return void
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetResourceFileIsValidWhenTheInfoOfTheResourceFileInfoIsCorrectlyReturned(): void
    {
        $this->resourceFileMock->documentFingerprint = 'file fingerprint';
        $result = $this->retrieveOriginalResource->getResourceFile(1, true);
        $resFileInfo = new ResourceFileInfo(
            null,
            null,
            [
            'dirname' => 'install/samples/resources/a/path',
            'basename' => 'ResourceConvertedTest',
            'filename' => 'ResourceConvertedTest'
        ],
            'original file content',
            'Maarch Courrier Test',
            'pdf'
        );

        $this->assertEquals($resFileInfo , $result);
    }

}
