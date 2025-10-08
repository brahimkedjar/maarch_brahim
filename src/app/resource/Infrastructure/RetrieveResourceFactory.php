<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve from Pastell factory
 * @author dev@maarch.org
 */

namespace Resource\Infrastructure;

use Resource\Application\RetrieveOriginalResource;
use Resource\Application\RetrieveResource;
use Resource\Application\RetrieveDocserverAndFilePath;
use Resource\Application\RetrieveThumbnailResource;
use Resource\Application\RetrieveThumbnailResourceByPage;
use Resource\Application\RetrieveVersionResource;

class RetrieveResourceFactory
{
    /**
     * @return RetrieveResource
     */
    public static function createRetrieveResource(): RetrieveResource
    {
        $resourceData = new ResourceData();
        $resourceFile = new ResourceFile();
        $resourceDocserverFilePathFingerPrint = new RetrieveDocserverAndFilePath($resourceData, $resourceFile);

        return new RetrieveResource($resourceData, $resourceFile, $resourceDocserverFilePathFingerPrint);
    }

    /**
     * @return RetrieveOriginalResource
     */
    public static function createRetrieveOriginalResource(): RetrieveOriginalResource
    {
        $resourceData = new ResourceData();
        $resourceFile = new ResourceFile();
        $resourceDocserverFilePathFingerPrint = new RetrieveDocserverAndFilePath($resourceData, $resourceFile);

        return new RetrieveOriginalResource($resourceData, $resourceFile, $resourceDocserverFilePathFingerPrint);
    }

    /**
     * @return RetrieveVersionResource
     */
    public static function createRetrieveVersionResource(): RetrieveVersionResource
    {
        $resourceData = new ResourceData();
        $resourceFile = new ResourceFile();
        $resourceDocserverFilePathFingerPrint = new RetrieveDocserverAndFilePath($resourceData, $resourceFile);

        return new RetrieveVersionResource($resourceData, $resourceFile, $resourceDocserverFilePathFingerPrint);
    }

    /**
     * @return RetrieveThumbnailResource
     */
    public static function createRetrieveThumbnailResource(): RetrieveThumbnailResource
    {
        $resourceData = new ResourceData();
        $resourceFile = new ResourceFile();
        $resourceDocserverFilePathFingerPrint = new RetrieveDocserverAndFilePath($resourceData, $resourceFile);

        return new RetrieveThumbnailResource($resourceData, $resourceFile, $resourceDocserverFilePathFingerPrint);
    }

    /**
     * @return RetrieveThumbnailResourceByPage
     */
    public static function createRetrieveThumbnailResourceByPage(): RetrieveThumbnailResourceByPage
    {
        $resourceData = new ResourceData();
        $resourceFile = new ResourceFile();
        $resourceLog  = new ResourceLog();

        return new RetrieveThumbnailResourceByPage($resourceData, $resourceFile, $resourceLog);
    }
}
