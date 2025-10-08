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
use Resource\Domain\Exceptions\ResourceDocserverDoesNotExistException;
use Resource\Domain\Exceptions\ResourceDoesNotExistException;
use Resource\Domain\Exceptions\ResourceFailedToGetDocumentFromDocserverException;
use Resource\Domain\Exceptions\ResourceFingerPrintDoesNotMatchException;
use Resource\Domain\Exceptions\ResourceHasNoFileException;
use Resource\Domain\Exceptions\ResourceNotFoundInDocserverException;
use Resource\Domain\Ports\ResourceFileInterface;
use Resource\Domain\ResourceFileInfo;

class RetrieveOriginalResource
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
     * Retrieves the resource file info.
     *
     * @param int $resId The ID of the resource.
     * @param bool $isSignedVersion (Optional) Whether to retrieve the signed version. Default is false.
     *
     * @return  ResourceFileInfo
     * @throws ParameterMustBeGreaterThanZeroException
     * @throws ResourceDoesNotExistException
     * @throws ResourceHasNoFileException
     * @throws ResourceFingerPrintDoesNotMatchException
     * @throws ResourceFailedToGetDocumentFromDocserverException
     * @throws ResourceDocserverDoesNotExistException
     * @throws ResourceNotFoundInDocserverException
     */
    public function getResourceFile(int $resId, bool $isSignedVersion = false): ResourceFileInfo
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

        $signedDocument = null;
        if ($isSignedVersion) {
            $signedDocument = $this->resourceRepository->getSignResourceData($resId, $document->getVersion());

            if ($signedDocument != null) {
                $signedDocument->setSubject($document->getSubject());
                $document = $signedDocument;
            }
        }

        $docserverAndFilePath = $this->retrieveResourceDocserverAndFilePath->getDocserverAndFilePath($document);

        $fingerPrint = $this->resourceFile->getFingerPrint(
            $docserverAndFilePath->getDocserver()->getDocserverTypeId(),
            $docserverAndFilePath->getFilePath()
        );
        if ($signedDocument == null && !empty($fingerPrint) && empty($document->getFingerprint())) {
            $this->resourceRepository->updateFingerprint($resId, $fingerPrint);
            $document->setFingerprint($fingerPrint);
        }
        if ($document->getFingerprint() != $fingerPrint) {
            throw new ResourceFingerPrintDoesNotMatchException();
        }

        $filename = $this->resourceRepository->formatFilename($document->getSubject());

        $fileContent = $this->resourceFile->getFileContent(
            $docserverAndFilePath->getFilePath(),
            $docserverAndFilePath->getDocserver()->getIsEncrypted()
        );
        if ($fileContent === null) {
            throw new ResourceFailedToGetDocumentFromDocserverException();
        }

        return new ResourceFileInfo(
            null,
            null,
            pathInfo($docserverAndFilePath->getFilePath()),
            $fileContent,
            $filename,
            $format
        );
    }
}
