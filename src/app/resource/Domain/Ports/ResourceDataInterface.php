<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Resource Data Interface
 * @author dev@maarch.org
 */

namespace Resource\Domain\Ports;

use Resource\Domain\Docserver;
use Resource\Domain\Resource;
use Resource\Domain\ResourceConverted;

interface ResourceDataInterface
{
    public const ADR_RESOURCE_TYPES = ['PDF', 'TNL', 'SIGN', 'NOTE'];

    public function getMainResourceData(int $resId): ?Resource;

    public function getSignResourceData(int $resId, int $version): ?ResourceConverted;

    public function getDocserverDataByDocserverId(string $docserverId): ?Docserver;

    public function updateFingerprint(int $resId, string $fingerprint): void;

    public function formatFilename(string $name, int $maxLength = 250): string;

    /**
     * Return the converted pdf from resource
     *
     * @param   int     $resId  Resource id
     * @param   string  $collId Resource type id : letterbox_coll or attachments_coll
     */
    public function getConvertedPdfById(int $resId, string $collId): array;

    /**
     * @param   int     $resId      Resource id
     * @param   string  $type       Resource converted format
     * @param   int     $version    Resource version
     *
     * @return  ?array
     */
    public function getResourceVersion(int $resId, string $type, int $version): ?array;

    /**
     * @param   int     $resId  Resource id
     * @param   string  $type   Resource converted format
     *
     * @return  ?ResourceConverted
     */
    public function getLatestResourceVersion(int $resId, string $type): ?ResourceConverted;

    /**
     * @param   int $resId      Resource id
     * @param   int $version    Resource version
     *
     * @return  ResourceConverted
     */
    public function getLatestPdfVersion(int $resId, int $version): ?ResourceConverted;

    /**
     * Check if user has rights over the resource
     *
     * @param   int     $resId      Resource id
     * @param   int     $userId     User id
     *
     * @return  bool
     */
    public function hasRightByResId(int $resId, int $userId): bool;
}
