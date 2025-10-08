<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

declare(strict_types=1);

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock;

use ExternalSignatoryBook\pastell\Application\ParseIParapheurLog;

class ParseIParapheurLogMock extends ParseIParapheurLog
{
    public ?int $errorResId = null;

    /**
     * @param int $resId
     * @param string $idFolder
     * @return string[]
     */
    public function parseLogIparapheur(int $resId, string $idFolder): array
    {
        if ($resId === 42) {
            return [
                'status'      => 'validated',
                'format'      => 'pdf',
                'encodedFile' => 'toto',
            ];
        }

        if ($resId === 41) {
            return [
                'status'      => 'validated',
                'format'      => 'pdf',
                'encodedFile' => 'toto',
                'signatory'   => 'Bruce Wayne - XELIANS'
            ];
        }

        if ($resId === 41) {
            return [
                'status'      => 'validated',
                'format'      => 'pdf',
                'encodedFile' => 'toto',
                'signatory'   => 'Bruce Wayne - XELIANS'
            ];
        }

        if ($resId === 152) {
            return [
                'res_id'      => 152,
                'external_id' => 'chuchu',
                'status'      => 'refused',
                'content'     => 'Un nom : une note'
            ];
        }

        if ($resId === $this->errorResId) {
            return [
                'error' => 'Could not parse log'
            ];
        }

        return [
            'status' => 'waiting'
        ];
    }

}
