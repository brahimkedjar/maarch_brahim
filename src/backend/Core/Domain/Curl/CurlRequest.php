<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CurlRequest class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Core\Domain\Curl;

use JsonSerializable;

class CurlRequest implements JsonSerializable
{
    private string $url = "";
    private string $method = "";
    private ?string $authBearer = null;
    private array $body = [];
    private ?CurlResponse $curlResponse;

    public function createFromArray(array $array = []): CurlRequest
    {
        $request = new CurlRequest();

        $request->setUrl($array['url']);
        $request->setMethod($array['method']);
        (!empty($array['authBearer'])) ? $request->setAuthBearer($array['authBearer']) : $request->setAuthBearer(null);
        (!empty($array['body'])) ? $request->setBody($array['body']) : $request->setBody([]);

        return $request;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    public function getAuthBearer(): ?string
    {
        return $this->authBearer;
    }

    public function setAuthBearer(?string $authBearer): void
    {
        $this->authBearer = $authBearer;
    }


    public function getCurlResponse(): ?CurlResponse
    {
        return $this->curlResponse;
    }

    public function setCurlResponse(?CurlResponse $curlResponse): void
    {
        $this->curlResponse = $curlResponse;
    }

    public function jsonSerialize(): array
    {
        return [
            'url'      => $this->getUrl(),
            'method'   => $this->getMethod(),
            'body'     => $this->getBody(),
            'response' => $this->getCurlResponse()
        ];
    }
}
