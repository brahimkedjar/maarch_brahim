<?php

namespace MaarchCourrier\Tests\unitTests\app\resource\Application;

use MaarchCourrier\Tests\app\resource\Mock\ResourceDataMock;
use MaarchCourrier\Tests\app\resource\Mock\ResourceFileMock;
use PHPUnit\Framework\TestCase;
use Resource\Application\RetrieveDocserverAndFilePath;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;
use Resource\Domain\ResourceConverted;

class RetrieveDocserverAndFilePathTest extends TestCase
{
    private ResourceDataMock $resourceDataMock;
    private ResourceFileMock $resourceFileMock;

    protected function setUp(): void
    {
        $this->resourceDataMock = new ResourceDataMock();
        $this->resourceFileMock = new ResourceFileMock();

        $this->retrieveDocserverAndFilePath =  new RetrieveDocserverAndFilePath($this->resourceDataMock, $this->resourceFileMock);
    }

    /**
     * @return void
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testDocserverAndFilePathReturnAnExceptionWhenTheResourceDocserverDoesNotExists(): void
    {
        $this->resourceDataMock->doesResourceDocserverExist = false;
        $document = new ResourceConverted(1,12,'type', 1, 'docId', 'a/path/', 'testDocserver', 'dsjhcbjb1544');

        $this->expectException(ResourceDocserverDoesNotExistException::class);
        $this->retrieveDocserverAndFilePath->getDocserverAndFilePath($document);
    }

    /**
     * @return void
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceNotFoundInDocserverException
     */
    public function testDocserverAndFilePathReturnAnExceptionWhenTheResourceIsNotFoundInDocserver(): void
    {
        $this->resourceFileMock->docserverPath = '';
        $document = new ResourceConverted(1,12,'type', 1, 'docId', 'a/path/', 'testDocserver', 'dsjhcbjb1544');

        $this->expectException(ResourceNotFoundInDocserverException::class);
        $this->retrieveDocserverAndFilePath->getDocserverAndFilePath($document);
    }
}
