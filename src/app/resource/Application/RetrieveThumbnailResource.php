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
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;
use Resource\Domain\Ports\ResourceFileInterface;
use Resource\Domain\ResourceConverted;
use Resource\Domain\ResourceFileInfo;

class RetrieveThumbnailResource
{
    private ResourceRepositoryInterface $resourceRepository;
    private ResourceFileInterface $resourceFile;
    private RetrieveDocserverAndFilePath $retrieveResourceDocserverAndFilePath;

    public function __construct(
        ResourceRepositoryInterface $resourceRepositoryInterface,
        ResourceFileInterface $resourceFileInterface,
        RetrieveDocserverAndFilePath $retrieveResourceDocserverAndFilePath
    ) {
        $this->resourceRepository = $resourceRepositoryInterface;
        $this->resourceFile = $resourceFileInterface;
        $this->retrieveResourceDocserverAndFilePath = $retrieveResourceDocserverAndFilePath;
    }

    /**
     * Retrieves the main file thumbnail info with watermark.
     *
     * @param int $resId The ID of the resource.
     *
     * @return  ResourceFileInfo
     *
     * @throws ResourceNotFoundInDocserverException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDoesNotExistException
     * @throws ConvertThumbnailException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceDocserverDoesNotExistException
     */
    public function getThumbnailFile(int $resId): ResourceFileInfo
    {
        if ($resId <= 0) {
            throw new ParameterMustBeGreaterThanZeroException('resId');
        }

        $document = $this->resourceRepository->getMainResourceData($resId);

        if ($document == null) {
            throw new ResourceDoesNotExistException();
        }

        $isDocserverEncrypted = false;
        $noThumbnailPath = 'dist/assets/noThumbnail.png';
        $pathToThumbnail = $noThumbnailPath;

        if (!empty($document->getFilename()) && $this->resourceRepository->hasRightByResId($resId, $GLOBALS['id'])) {
            $tnlDocument = $this->getResourceVersion($resId, 'TNL', $document->getVersion());

            if ($tnlDocument == null) {
                $latestPdfVersion = $this->resourceRepository->getLatestPdfVersion($resId, $document->getVersion());
                if ($latestPdfVersion == null) {
                    throw new ResourceDoesNotExistException();
                }

                $docserverAndFilePath = $this->retrieveResourceDocserverAndFilePath
                    ->getDocserverAndFilePath($latestPdfVersion);

                $fileContent = $this->resourceFile->getFileContent(
                    $docserverAndFilePath->getFilePath(),
                    $docserverAndFilePath->getDocserver()->getIsEncrypted()
                );
                if ($fileContent === null) {
                    throw new ResourceFailedToGetDocumentFromDocserverException();
                }

                $check = $this->resourceFile->convertToThumbnail(
                    $resId,
                    $latestPdfVersion->getVersion(),
                    $fileContent,
                    pathinfo($docserverAndFilePath->getFilePath(), PATHINFO_EXTENSION)
                );
                if (isset($check['errors'])) {
                    throw new ConvertThumbnailException($check['errors']);
                }
                $tnlDocument = $this->getResourceVersion($resId, 'TNL', $document->getVersion());
            }

            if ($tnlDocument != null) {
                $checkDocserver =
                    $this->resourceRepository->getDocserverDataByDocserverId($tnlDocument->getDocserverId());

                $isDocserverEncrypted = $checkDocserver->getIsEncrypted() ?? false;

                $pathToThumbnail = $this->resourceFile->buildFilePath(
                    $checkDocserver->getPathTemplate(),
                    $tnlDocument->getPath(),
                    $tnlDocument->getFilename()
                );

                if (!$this->resourceFile->fileExists($pathToThumbnail)) {
                    throw new ResourceNotFoundInDocserverException();
                }
            }
        }

        $pathInfo = pathinfo($pathToThumbnail);
        $fileContent = $this->resourceFile->getFileContent($pathToThumbnail, $isDocserverEncrypted);

        if ($fileContent === null) {
            $pathInfo = pathinfo($noThumbnailPath);
            $fileContent = $this->resourceFile->getFileContent($noThumbnailPath);
        }

        return new ResourceFileInfo(
            null,
            null,
            $pathInfo,
            $fileContent,
            "maarch.{$pathInfo['extension']}",
            ""
        );
    }

    /**
     * @param int $resId
     * @param string $type
     * @param int $version
     * @return ResourceConverted|null
     */
    private function getResourceVersion(int $resId, string $type, int $version): ?ResourceConverted
    {
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
}
