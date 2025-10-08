<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurlResponse class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Curl;

use JsonSerializable;

class CurlResponse implements JsonSerializable
{
    public function __construct(private int $httpCode, private array $contentReturn)
    {
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    public function getContentReturn(): array
    {
        return $this->contentReturn;
    }

    public function setContentReturn(array $contentReturn): void
    {
        $this->contentReturn = $contentReturn;
    }

    public function jsonSerialize(): array
    {
        return [
            'httpCode'      => $this->httpCode,
            'contentReturn' => $this->contentReturn
        ];
    }
}
