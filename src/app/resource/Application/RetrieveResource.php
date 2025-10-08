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
use Resource\Domain\Exceptions\ConvertedResultException;
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceFingerPrintDoesNotMatchException;
use Resource\Domain\Exceptions\ResourceHasNoFileException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;
use Resource\Domain\Ports\ResourceFileInterface;
use Resource\Domain\ResourceConverted;
use Resource\Domain\ResourceFileInfo;

class RetrieveResource
{
    private ResourceRepositoryInterface $resourceRepository;
    private ResourceFileInterface $resourceFile;
    private RetrieveDocserverAndFilePath $retrieveResourceDocserverAndFilePath;

    /**
     * @param ResourceRepositoryInterface $resourceRepositoryInterface
     * @param ResourceFileInterface $resourceFileInterface
     * @param RetrieveDocserverAndFilePath $retrieveResourceDocserverAndFilePath
     */
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
     * Retrieves the main file info with watermark.
     *
     * @param int $resId The ID of the resource.
     * @param bool $watermark
     * @return ResourceFileInfo
     * @throws ConvertedResultException
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceDoesNotExistException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceHasNoFileException
     * @throws ResourceNotFoundInDocserverException
     */
    public function getResourceFile(int $resId, bool $watermark): ResourceFileInfo
    {
        if ($resId <= 0) {
            throw new ParameterMustBeGreaterThanZeroException('resId');
        }

        $document = $this->resourceRepository->getMainResourceData($resId);

        if ($document == null) {
            throw new ResourceDoesNotExistException();
        } elseif (empty($document->getFilename())) {
            throw new ResourceHasNoFileException();
        }

        $format = $document->getFormat();
        $subject = $document->getSubject();
        $creatorId = $document->getTypist();

        $document = $this->getConvertedResourcePdfById($resId);

        $docserverAndFilePath = $this->retrieveResourceDocserverAndFilePath->getDocserverAndFilePath($document);

        $fingerPrint = $this->resourceFile->getFingerPrint(
            $docserverAndFilePath->getDocserver()->getDocserverTypeId(),
            $docserverAndFilePath->getFilePath()
        );
        if (!empty($fingerPrint) && empty($document->getFingerprint())) {
            $this->resourceRepository->updateFingerprint($resId, $fingerPrint);
            $document->setFingerprint($fingerPrint);
        }

        if ($document->getFingerprint() != $fingerPrint) {
            throw new ResourceFingerPrintDoesNotMatchException();
        }

        $fileContentWithNoWatermark = $this->resourceFile->getFileContent(
            $docserverAndFilePath->getFilePath(),
            $docserverAndFilePath->getDocserver()->getIsEncrypted()
        );

        if ($watermark) {
            $fileContent = $this->resourceFile->getWatermark($resId, $fileContentWithNoWatermark);
            if (empty($fileContent)) {
                $fileContent = $fileContentWithNoWatermark;
            }
        } else {
            $fileContent = $fileContentWithNoWatermark;
        }

        if ($fileContent === null) {
            throw new ResourceFailedToGetDocumentFromDocserverException();
        }

        $filename = $this->resourceRepository->formatFilename($subject);

        return new ResourceFileInfo(
            $creatorId,
            null,
            pathInfo($docserverAndFilePath->getFilePath()),
            $fileContent,
            $filename,
            $format
        );
    }

    /**
     * @param int $resId
     * @return ResourceConverted
     * @throws ConvertedResultException
     */
    private function getConvertedResourcePdfById(int $resId): ResourceConverted
    {
        $document = $this->resourceRepository->getConvertedPdfById($resId, 'letterbox_coll');

        if (!empty($document['errors'])) {
            throw new ConvertedResultException($document['errors']);
        }

        return new ResourceConverted(
            $document['id'] ?? 0,
            $resId,
            '',
            0,
            $document['docserver_id'],
            $document['path'],
            $document['filename'],
            $document['fingerprint']
        );
    }
}
