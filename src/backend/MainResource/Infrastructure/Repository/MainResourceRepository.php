<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MainResourceRepository class
 * @author dev@maarch.org
 */

declare(strict_types=1);

namespace MaarchCourrier\MainResource\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceRepositoryInterface;
use MaarchCourrier\Core\Domain\User\Port\UserFactoryInterface;
use MaarchCourrier\DocumentStorage\Domain\Document;
use MaarchCourrier\MainResource\Domain\Integration;
use MaarchCourrier\MainResource\Domain\MainResource;
use Resource\models\ResModel;

class MainResourceRepository implements MainResourceRepositoryInterface
{
    public function __construct(
        private readonly UserFactoryInterface $userFactory
    ) {
    }

    /**
     * @param int $resId
     *
     * @return ?MainResourceInterface
     * @throws Exception
     */
    public function getMainResourceByResId(int $resId): ?MainResourceInterface
    {
        $resource = ResModel::getById(['resId'  => $resId, 'select' => ['*']]);

        if (empty($resource)) {
            return null;
        }

        $typist = $this->userFactory->createUserFromArray(['id' => $resource['typist']]);

        $document = (new Document())
            ->setFileName($resource['filename'] ?? '')
            ->setFileExtension($resource['format'] ?? '');
        $integration = (new Integration())->createFromArray(json_decode($resource['integrations'], true));

        return (new MainResource())
            ->setResId($resId)
            ->setSubject($resource['subject'])
            ->setChrono($resource['alt_identifier'])
            ->setTypist($typist)
            ->setDocument($document)
            ->setIntegration($integration);
    }
}
