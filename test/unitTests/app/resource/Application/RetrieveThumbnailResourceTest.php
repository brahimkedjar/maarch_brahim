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
use Resource\Application\RetrieveThumbnailResource;
use Resource\Domain\Exceptions\ConvertThumbnailException;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;

class RetrieveThumbnailResourceTest extends TestCase
{
    private ResourceDataMock $resourceDataMock;
    private ResourceFileMock $resourceFileMock;
    private RetrieveThumbnailResource $retrieveThumbnailResource;

    protected function setUp(): void
    {
        $this->resourceDataMock = new ResourceDataMock();
        $this->resourceFileMock = new ResourceFileMock();

        $this->retrieveThumbnailResource = new RetrieveThumbnailResource(
            $this->resourceDataMock,
            $this->resourceFileMock,
            new RetrieveDocserverAndFilePath($this->resourceDataMock, $this->resourceFileMock)
        );
    }

    /**
     * @return void
     * @throws ConvertThumbnailException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDoesNotExistException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetThumbnailFileBecauseResId0(): void
    {
        $this->expectExceptionObject(new ParameterMustBeGreaterThanZeroException('resId'));

        $this->retrieveThumbnailResource->getThumbnailFile(0);
    }

    /**
     * @return void
     * @throws ConvertThumbnailException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetThumbnailFileBecauseResourceHasNoFileExpectNoThumbnailFile(): void
    {
        $this->resourceDataMock->returnResourceWithoutFile = true;
        $this->resourceFileMock->returnResourceThumbnailFileContent = true;

        $result = $this->retrieveThumbnailResource->getThumbnailFile(1);

        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertSame($result->getFormatFilename(), 'maarch.png');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->noThumbnailFileContent);
    }

    /**
     * @return void
     * @throws ConvertThumbnailException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetThumbnailFileBecauseGlobalUserHasNoRightsExpectNoThumbnailFile(): void
    {
        $this->resourceDataMock->doesUserHasRights = false;
        $this->resourceFileMock->returnResourceThumbnailFileContent = true;

        $result = $this->retrieveThumbnailResource->getThumbnailFile(1);

        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertSame($result->getFormatFilename(), 'maarch.png');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->noThumbnailFileContent);
    }

    /**
     * @return void
     * @throws ConvertThumbnailException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetThumbnailFileBecauseOfNoDocumentVersionAndThumbnailConversionFailed(): void
    {
        $this->resourceDataMock->doesResourceVersionExist = false;
        $this->resourceFileMock->doesResourceConvertToThumbnailFailed = true;

        $this->expectExceptionObject(new ConvertThumbnailException('Conversion to thumbnail failed'));

        $this->retrieveThumbnailResource->getThumbnailFile(1);
    }

    /**
     * @return void
     * @throws ConvertThumbnailException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testCannotGetThumbnailFileBecauseResourceFailedToGetContentFromDocserverExpectNoThumbnailFile(): void
    {
        $this->resourceFileMock->doesResourceFileGetContentFail = true;
        $this->resourceFileMock->returnResourceThumbnailFileContent = true;

        $result = $this->retrieveThumbnailResource->getThumbnailFile(1);

        $this->assertNotEmpty($result->getPathInfo());
        $this->assertNotEmpty($result->getFileContent());
        $this->assertNotEmpty($result->getFormatFilename());
        $this->assertSame($result->getFormatFilename(), 'maarch.png');
        $this->assertSame($result->getFileContent(), $this->resourceFileMock->noThumbnailFileContent);
    }

    /**
     * @return void
     * @throws ConvertThumbnailException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testGetThumbnailFileReturnAnExceptionWhenDocumentIsnull(): void
    {
        $this->resourceDataMock->doesResourceExist = false;

        $this->expectException(ResourceDoesNotExistException::class);

        $this->retrieveThumbnailResource->getThumbnailFile(1);
    }

    /**
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ConvertThumbnailException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testRetrieveThumbnailResourceReturnAnExceptionWhenThereIsNoPdfVersion(): void
    {
        $this->resourceDataMock->doesResourceVersionExist = false;
        $this->resourceDataMock->latestPdfVersionExist = false;

        $this->expectException(ResourceDoesNotExistException::class);

        $this->retrieveThumbnailResource->getThumbnailFile(1);
    }

    public function testRetrieveThumbnailResourceReturnAnExceptionWhenTheResourceFailedToGetDocumentFromDocserver(): void
    {
        $this->resourceDataMock->doesResourceVersionExist = false;
        $this->resourceFileMock->doesResourceFileGetContentFail = true;

        $this->expectException(ResourceFailedToGetDocumentFromDocserverException::class);

        $this->retrieveThumbnailResource->getThumbnailFile(1);
    }
}
