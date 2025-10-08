<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   StoreSignedResourceService mock
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Mock\Webhook;

use MaarchCourrier\SignatureBook\Domain\Port\StoreSignedResourceServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignedResource;

class StoreSignedResourceServiceMock implements StoreSignedResourceServiceInterface
{
    public bool $errorStorage = false;
    public int $resIdNewSignedDoc = -1;

    public function storeResource(SignedResource $signedResource): array
    {
        if ($this->errorStorage) {
            return ['errors' => '[storeRessourceOnDocserver] Error during storing signed response'];
        }

        $path_template = 'install/samples/resources/';
        $destinationDir = $path_template . '2023/11/0001/';
        $directory = substr($destinationDir, strlen($path_template));

        return [
            'path_template'         => $path_template,
            'destination_dir'       => $directory,
            'directory'             => $directory,
            'docserver_id'          => 'FASTHD_MAN',
            'file_destination_name' => 'toto.pdf',
            'fileSize'              => 56899,
            'fingerPrint'           => "file fingerprint"
        ];
    }

    public function storeAttachement(SignedResource $signedResource, array $attachment): int|array
    {
        if ($this->errorStorage) {
            return ['errors' => 'Error on attachment storage'];
        }
        return $this->resIdNewSignedDoc;
    }
}
