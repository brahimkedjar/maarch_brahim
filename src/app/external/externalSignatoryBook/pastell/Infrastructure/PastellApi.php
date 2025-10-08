<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Pastell API
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\pastell\Infrastructure;

use Exception;
use ExternalSignatoryBook\pastell\Domain\PastellApiInterface;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use SrcCore\models\CurlModel;

class PastellApi implements PastellApiInterface
{
    /**
     * Getting Pastell version (Checking if URL, login and password are correct)
     *
     * @param PastellConfig $config
     *
     * @return array
     * @throws Exception
     */
    public function getVersion(PastellConfig $config): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/version',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET'
        ]);

        if ($response['code'] > 200) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = ['version' => $response['response']['version'] ?? ''];
        }
        return $return;
    }

    /**
     * Getting the connected entity
     *
     * @param PastellConfig $config
     *
     * @return array|string[]
     * @throws Exception
     */
    public function getEntity(PastellConfig $config): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET'
        ]);

        if ($response['code'] > 200) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = [];
            foreach ($response['response'] as $entite) {
                $return = ['entityId' => $entite['id_e']];
            }
        }
        return $return;
    }

    /**
     * Getting the plugged connector
     *
     * @param PastellConfig $config
     *
     * @return array|string[]
     * @throws Exception
     */
    public function getConnector(PastellConfig $config): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/connecteur',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET'
        ]);

        if ($response['code'] > 200) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = [];
            foreach ($response['response'] as $connector) {
                $return[] = $connector['id_ce'];
            }
        }
        return $return;
    }

    /**
     * Getting the type of folder(document) that can be created
     *
     * @param PastellConfig $config
     *
     * @return array
     * @throws Exception
     */
    public function getFolderType(PastellConfig $config): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/flux',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET'
        ]);

        if ($response['code'] > 200) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = [];
            foreach ($response['response'] as $flux => $key) {
                $return[] = $flux;
            }
        }
        return $return;
    }

    /**
     * Getting the type of the plugged connector
     *
     * @param PastellConfig $config
     *
     * @return array
     * @throws Exception
     */
    public function getIparapheurType(PastellConfig $config): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/connecteur/' .
                $config->getConnector() . '/externalData/iparapheur_type',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET'
        ]);

        if ($response['code'] > 200) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = [];
            foreach ($response['response'] as $iParapheurType) {
                $return[] = $iParapheurType;
            }
        }
        return $return;
    }

    /**
     * Creating a folder
     *
     * @param PastellConfig $config
     *
     * @return array|string[]
     * @throws Exception
     */
    public function createFolder(PastellConfig $config): array
    {
        $response = CurlModel::exec([
            'url'         => $config->getUrl() . '/entite/' . $config->getEntity() . '/document',
            'basicAuth'   => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'headers'     => ['content-type:application/json'],
            'method'      => 'POST',
            'queryParams' => ['type' => $config->getFolderType()],
            'body'        => json_encode([])
        ]);

        if ($response['code'] > 201) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = ['idFolder' => $response['response']['info']['id_d'] ?? ''];
        }
        return $return;
    }

    /**
     * Getting subtype of the plugged connector
     *
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return array
     * @throws Exception
     */
    public function getIparapheurSousType(PastellConfig $config, string $idFolder): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/externalData/iparapheur_sous_type',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET'
        ]);

        if ($response['code'] > 200) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = $response['response'] ?? '';
        }
        return $return;
    }

    /**
     * Sending datas to the created folder
     *
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $title
     * @param string $sousType
     *
     * @return array|string[]
     * @throws Exception
     */
    public function editFolder(PastellConfig $config, string $idFolder, string $title, string $sousType): array
    {
        $data = [
            'libelle'              => $title,
            'iparapheur_sous_type' => $sousType,
            'iparapheur_type'      => $config->getIparapheurType(),
            'envoi_signature'      => true
        ];

        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder,
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'method'    => 'PATCH',
            'body'      => http_build_query($data)
        ]);

        if ($response['code'] > 200) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = $response['response'] ?? '';
        }

        return $return;
    }

    /**
     * Uploading a file to be signed
     *
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $filePath
     *
     * @return array|string[]
     * @throws Exception
     */
    public function uploadMainFile(PastellConfig $config, string $idFolder, string $filePath): array
    {
        $bodyData = [
            'file_name'    => 'Document principal.' . pathinfo($filePath)['extension'],
            'file_content' => file_get_contents($filePath)
        ];

        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/file/document',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'method'    => 'POST',
            'body'      => http_build_query($bodyData)
        ]);

        $return = [];
        if ($response['code'] > 201) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        }
        return $return;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     * @param string $filePath
     * @param int $nbAttachments
     *
     * @return array|string[]
     * @throws Exception
     */
    public function uploadAttachmentFile(
        PastellConfig $config,
        string $idFolder,
        string $filePath,
        int $nbAttachments
    ): array {
        $bodyData = [
            'file_name'    => 'PJ' . ($nbAttachments + 1) . '.' . pathinfo($filePath)['extension'],
            'file_content' => file_get_contents($filePath)
        ];

        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/file/annexe/' . $nbAttachments,
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'method'    => 'POST',
            'body'      => http_build_query($bodyData)
        ]);

        $return = [];
        if ($response['code'] > 201) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        }
        return $return;
    }

    /**
     * Sending folder to iParapheur
     *
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return array
     * @throws Exception
     */
    public function orientation(PastellConfig $config, string $idFolder): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/action/orientation',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'POST'
        ]);

        if ($response['code'] > 201) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = $response['response'];
        }
        return $return;
    }

    /**
     * Getting datas and state of a folder
     *
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return array|string[]
     * @throws Exception
     */
    public function getFolderDetail(PastellConfig $config, string $idFolder): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder,
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET'
        ]);

        if ($response['code'] > 201) {
            $return = ['code' => $response['code']];
            if (!empty($response['response']['error-message'])) {
                $return["error"] = $response['response']['error-message'];
            } else {
                $return["error"] = 'An error occurred !';
            }
        } else {
            $return =
                [
                    'info'            => $response['response']['info'] ?? [],
                    'data'            => $response['response']['data'] ?? [],
                    'actionPossibles' => $response['response']['action_possible'] ?? [],
                    'lastAction'      => $response['response']['last_action'] ?? []
                ];
        }
        return $return;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return object
     * @throws Exception
     */
    public function getXmlDetail(PastellConfig $config, string $idFolder): object
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/file/iparapheur_historique',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'    => 'GET',
            'isXml'     => true
        ]);

        if ($response['code'] > 201) {
            $return = new \stdClass();
            if (!empty($response['response']['error-message'])) {
                $return->error = $response['response']['error-message'];
            } else {
                $return->error = 'An error occurred !';
            }
        } else {
            $return = $response['response'];
        }
        return $return;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return array
     * @throws Exception
     */
    public function downloadFile(PastellConfig $config, string $idFolder): array
    {
        $response = CurlModel::exec([
            'url'          => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/file/document',
            'basicAuth'    => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'method'       => 'GET',
            'fileResponse' => true
        ]);

        if ($response['code'] > 201) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = ['encodedFile' => base64_encode($response['response'])];
        }
        return $return;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return bool
     * @throws Exception
     */
    public function verificationIParapheur(PastellConfig $config, string $idFolder): bool
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/action/verif-iparapheur',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'method'    => 'POST',
        ]);

        return $response['code'] == 201;
    }

    /**
     * Sending a folder with data and main file
     *
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return bool
     * @throws Exception
     */
    public function sendIparapheur(PastellConfig $config, string $idFolder): bool
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/api/v2' . '/entite/' . $config->getEntity() . '/document/' .
                $idFolder . '/action/send-iparapheur',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'method'    => 'POST',
        ]);

        return $response['code'] == 200;
    }

    /**
     * @param PastellConfig $config
     * @param string $idFolder
     *
     * @return array|string[]
     * @throws Exception
     */
    public function deleteFolder(PastellConfig $config, string $idFolder): array
    {
        $response = CurlModel::exec([
            'url'       => $config->getUrl() . '/entite/' . $config->getEntity() . '/document/' . $idFolder .
                '/action/supression',
            'basicAuth' => ['user' => $config->getLogin(), 'password' => $config->getPassword()],
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'method'    => 'POST',
        ]);

        if ($response['code'] > 201) {
            if (!empty($response['response']['error-message'])) {
                $return = ["error" => $response['response']['error-message']];
            } else {
                $return = ["error" => 'An error occurred !'];
            }
        } else {
            $return = $response['response'];
        }
        return $return;
    }
}
