<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve from Docserver
 * @author dev@maarch.org
 */

namespace Resource\Application;

use MaarchCourrier\Core\Domain\MainResource\Port\ResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\Problem\ParameterMustBeGreaterThanZeroException;
use Resource\Domain\Exceptions\ConvertThumbnailException;
use Resource\Domain\Exceptions\ParameterCanNotBeEmptyException;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;
use Resource\Domain\Exceptions\ResourceOutOfPerimeterException;
use Resource\Domain\Exceptions\ResourcePageNotFoundException;
use Resource\Domain\Exceptions\SetaPdfResultException;
use Resource\Domain\Exceptions\ThumbnailNotFoundInDocserverOrNotReadableException;
use Resource\Domain\Ports\ResourceFileInterface;
use Resource\Domain\Ports\ResourceLogInterface;
use Resource\Domain\ResourceConverted;
use Resource\Domain\ResourceFileInfo;
use Throwable;

class RetrieveThumbnailResourceByPage
{
    private ResourceRepositoryInterface $resourceRepository;
    private ResourceFileInterface $resourceFile;
    private ResourceLogInterface $resourceLog;

    public function __construct(
        ResourceRepositoryInterface $resourceRepositoryInterface,
        ResourceFileInterface $resourceFileInterface,
        ResourceLogInterface $resourceLog
    ) {
        $this->resourceRepository = $resourceRepositoryInterface;
        $this->resourceFile = $resourceFileInterface;
        $this->resourceLog = $resourceLog;
    }

    /**
     * Retrieves thumbnail of resource by page number.
     * @param int $resId The ID of the resource.
     * @param int $page The ID of the resource.
     * @return ResourceFileInfo
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDoesNotExistException
     * @throws ResourceOutOfPerimeterException
     * @throws ParameterCanNotBeEmptyException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ConvertThumbnailException
     * @throws ThumbnailNotFoundInDocserverOrNotReadableException
     * @throws ResourcePageNotFoundException
     * @throws ResourceNotFoundInDocserverException
     * @throws SetaPdfResultException
     */
    public function getThumbnailFileByPage(int $resId, int $page): ResourceFileInfo
    {
        if ($resId <= 0) {
            throw new ParameterMustBeGreaterThanZeroException('resId');
        }
        if ($page <= 0) {
            throw new ParameterMustBeGreaterThanZeroException('page');
        }

        $document = $this->resourceRepository->getMainResourceData($resId);
        if ($document == null) {
            throw new ResourceDoesNotExistException();
        }

        if (!$this->resourceRepository->hasRightByResId($resId, $GLOBALS['id'])) {
            throw new ResourceOutOfPerimeterException();
        }

        $check = $this->resourceFile->convertOnePageToThumbnail($resId, 'resource', $page);
        if (str_contains($check, 'errors:')) {
            throw new ConvertThumbnailException($check);
        }

        $adr = $this->getResourceVersionThumbnailByPage($resId, "TNL$page", $document->getVersion());

        list($adrDocserver, $pathToThumbnail) = $this->buildFilePath($adr);

        if (!$this->resourceFile->fileExists($pathToThumbnail)) {
            throw new ThumbnailNotFoundInDocserverOrNotReadableException();
        }

        $fileContent = $this->resourceFile->getFileContent($pathToThumbnail, $adrDocserver->getIsEncrypted());
        if ($fileContent === null) {
            throw new ResourcePageNotFoundException();
        }

        $filename = $this->resourceRepository->formatFilename($document->getSubject());

        // Get latest pdf version before to get the page count
        $document = $this->resourceRepository->getLatestResourceVersion($resId, 'PDF');

        list($adrDocserver, $pathToPdfDocument) = $this->buildFilePath($document);

        if (!$this->resourceFile->fileExists($pathToPdfDocument)) {
            throw new ResourceNotFoundInDocserverException();
        }

        try {
            $pageCount = $this->resourceFile->getTheNumberOfPagesInThePdfFile($pathToPdfDocument);
        } catch (Throwable $th) {
            $this->resourceLog->logThumbnailEvent('ERROR', $resId, $th->getMessage());
            throw new SetaPdfResultException($th->getMessage());
        }

        return new ResourceFileInfo(
            null,
            $pageCount,
            pathInfo($pathToThumbnail),
            $fileContent,
            $filename,
            ''
        );
    }

    /**
     * @param int $resId
     * @param string $type
     * @param int $version
     * @return ResourceConverted|null
     * @throws ParameterCanNotBeEmptyException
     */
    private function getResourceVersionThumbnailByPage(int $resId, string $type, int $version): ?ResourceConverted
    {
        $checkThumbnailPageType = ctype_digit(str_replace('TNL', '', $type));
        if (
            empty($type) ||
            (!in_array($type, $this->resourceRepository::ADR_RESOURCE_TYPES) && !$checkThumbnailPageType)
        ) {
            throw new ParameterCanNotBeEmptyException(
                'type',
                implode(
                    ', ',
                    $this->resourceRepository::ADR_RESOURCE_TYPES
                ) . " or thumbnail page 'TNL*'"
            );
        }

        $document = $this->resourceRepository->getResourceVersion($resId, $type, $version);

        if ($document == null) {
            return null;
        }

        return new ResourceConverted(
            $document['id'],
            $resId,
            $type,
            $version,
            $document['docserver_id'],
            $document['path'],
            $document['filename'],
            $document['fingerprint']
        );
    }

    /**
     * @param ResourceConverted|null $resourceConverted
     * @return array
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     */
    private function buildFilePath(?ResourceConverted $resourceConverted): array
    {
        if ($resourceConverted == null) {
            throw new ResourceDoesNotExistException();
        }

        $adrDocserver = $this->resourceRepository->getDocserverDataByDocserverId($resourceConverted->getDocserverId());
        if ($adrDocserver == null || !$this->resourceFile->folderExists($adrDocserver->getPathTemplate())) {
            throw new ResourceDocserverDoesNotExistException();
        }

        $pathToThumbnail = $this->resourceFile->buildFilePath(
            $adrDocserver->getPathTemplate(),
            $resourceConverted->getPath(),
            $resourceConverted->getFilename()
        );

        return [$adrDocserver, $pathToThumbnail];
    }
}
