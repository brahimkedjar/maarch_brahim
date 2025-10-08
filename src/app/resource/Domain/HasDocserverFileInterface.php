<?php

namespace Resource\Domain;

interface HasDocserverFileInterface
{
    public function getDocserverId(): ?string;
    public function getPath(): ?string;
    public function getFilename(): ?string;
}
