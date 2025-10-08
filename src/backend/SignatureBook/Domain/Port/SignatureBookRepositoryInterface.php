<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief SignatureBookRepositoryInterface class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Domain\Port;

use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;

interface SignatureBookRepositoryInterface
{
    /**
     * @param MainResourceInterface $mainResource
     * @param UserInterface $user
     *
     * @return bool
     */
    public function canUpdateResourcesInSignatureBook(MainResourceInterface $mainResource, UserInterface $user): bool;

    /**
     * @param MainResourceInterface $mainResource
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isMainResourceInSignatureBookBasket(MainResourceInterface $mainResource, UserInterface $user): bool;
}
