<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Custom Automatic Update Interface
 * @author dev@maarch.org
 * @ingroup core
 */

namespace SrcCore\interfaces;

interface AutoUpdateInterface
{
    /**
     * Function to perform any backups for the update.
     * Use trycatch at the root of the function.
     *
     * @return  void   If backup is successful
     * @throws  \Exception If the backup failed
     */
    public function backup(): void;

    /**
     * Function to perform any update for a feature.
     * Use trycatch at the root of the function.
     *
     * @return  void   If update is successful
     * @throws  \Exception If the update failed
     */
    public function update(): void;

    /**
     * Function to perform any rollback for the update.
     * Use trycatch at the root of the function.
     *
     * @return  void   If rollback is successful
     * @throws  \Exception If the rollback failed
     */
    public function rollback(): void;
}
