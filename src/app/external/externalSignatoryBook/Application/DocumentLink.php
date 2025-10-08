<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief DocumentLink class
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\Application;

use ExternalSignatoryBook\Domain\Exceptions\ParameterCanNotBeEmptyException;
use ExternalSignatoryBook\Domain\Exceptions\ParameterMustBeGreaterThanZeroException;
use ExternalSignatoryBook\Domain\Ports\AttachmentRepositoryInterface;
use ExternalSignatoryBook\Domain\Ports\HistoryRepositoryInterface;
use ExternalSignatoryBook\Domain\Ports\ResourceRepositoryInterface;
use ExternalSignatoryBook\Domain\Ports\UserRepositoryInterface;

class DocumentLink
{
    public const DOCUMENT_TYPES = ['resource', 'attachment'];
    private UserRepositoryInterface $userRepository;
    private ResourceRepositoryInterface $resourceRepository;
    private AttachmentRepositoryInterface $attachmentRepository;
    private HistoryRepositoryInterface $historyRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ResourceRepositoryInterface $resourceRepositoryInterface,
        AttachmentRepositoryInterface $attachmentRepositoryInterface,
        HistoryRepositoryInterface $historyRepositoryInterface
    ) {
        $this->userRepository = $userRepository;
        $this->resourceRepository = $resourceRepositoryInterface;
        $this->attachmentRepository = $attachmentRepositoryInterface;
        $this->historyRepository = $historyRepositoryInterface;
    }

    /**
     * @throws ParameterCanNotBeEmptyException
     * @throws ParameterMustBeGreaterThanZeroException
     */
    public function removeExternalLink(
        int $docItemResId,
        string $docItemTitle,
        string $type,
        string $docItemExternalId
    ): void {
        if ($docItemResId <= 0) {
            throw new ParameterMustBeGreaterThanZeroException('docItemResId');
        }
        if (empty($docItemTitle)) {
            throw new ParameterCanNotBeEmptyException('docItemTitle');
        }
        if (empty($type)) {
            throw new ParameterCanNotBeEmptyException('type');
        }
        if (!in_array($type, $this::DOCUMENT_TYPES)) {
            throw new ParameterCanNotBeEmptyException('type', implode(' or ', $this::DOCUMENT_TYPES));
        }
        if (empty($docItemExternalId)) {
            throw new ParameterCanNotBeEmptyException('docItemExternalId');
        }

        $rootUser = $this->userRepository->getRootUser();

        // remove signatureBookId link
        $historyMessage = '';
        if ($type === 'resource') {
            $this->resourceRepository->removeExternalLink($docItemResId, $docItemExternalId);
            $historyMessage = _DOC_DOES_NOT_EXIST_IN_EXTERNAL_SIGNATORY;
        } else {
            $this->attachmentRepository->removeExternalLink($docItemResId, $docItemExternalId);
            $historyMessage = _ATTACH_DOES_NOT_EXIST_IN_EXTERNAL_SIGNATORY[0] . " '$docItemTitle' " .
                _ATTACH_DOES_NOT_EXIST_IN_EXTERNAL_SIGNATORY[1];
        }

        $this->historyRepository->addHistoryForResource((string)$docItemResId, $rootUser->getId(), $historyMessage);
    }
}
