<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Config Test class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Application\Config;

use MaarchCourrier\SignatureBook\Application\Config\RetrieveConfig;
use MaarchCourrier\SignatureBook\Domain\SignatureBookConfigReturnApi;
use MaarchCourrier\Tests\Unit\Core\Mock\EnvironmentMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Config\SignatureServiceConfigLoaderMock;
use PHPUnit\Framework\TestCase;

class RetrieveConfigTest extends TestCase
{
    private EnvironmentMock $environmentMock;
    private RetrieveConfig $retrieveConfig;
    private SignatureServiceConfigLoaderMock $signatureBookConfigRepositoryMock;

    protected function setUp(): void
    {
        $this->environmentMock = new EnvironmentMock();
        $this->signatureBookConfigRepositoryMock = new SignatureServiceConfigLoaderMock();
        $this->retrieveConfig = new RetrieveConfig($this->environmentMock, $this->signatureBookConfigRepositoryMock);
    }

    public function testGetDefaultConfigAndExpectParameterNewInternalParaphToBeFalse(): void
    {
        $config = $this->retrieveConfig->getConfig();

        $this->assertInstanceOf(SignatureBookConfigReturnApi::class, $config);
        $this->assertFalse($config->isNewInternalParaph());
        $this->assertEmpty($config->getUrl());
    }

    public function testGetConfigWhenNewInternalParaphIsActive(): void
    {
        $this->environmentMock->isNewInternalParapheurEnabled = true;

        $config = $this->retrieveConfig->getConfig();

        $this->assertInstanceOf(SignatureBookConfigReturnApi::class, $config);
        $this->assertTrue($config->isNewInternalParaph());
        $this->assertNotEmpty($config->getUrl());
        $this->assertSame($this->signatureBookConfigRepositoryMock->url, $config->getUrl());
    }
}
