<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maarch Parapheur User Service
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure;

use Exception;
use MaarchCourrier\Core\Domain\User\Port\UserInterface;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureBookUserServiceInterface;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;
use SrcCore\models\CurlModel;

class MaarchParapheurUserService implements SignatureBookUserServiceInterface
{
    public int $id;
    private SignatureBookServiceConfig $config;

    /**
     * @param SignatureBookServiceConfig $config
     *
     * @return SignatureBookUserServiceInterface
     */
    public function setConfig(SignatureBookServiceConfig $config): SignatureBookUserServiceInterface
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param int $id
     * @return true
     * @throws Exception
     */
    public function doesUserExists(int $id): bool
    {
        $response = CurlModel::exec([
            'url'       => rtrim($this->config->getUrl(), '/') . '/rest/users/' . $id,
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'    => 'GET',
            'headers'   => [
                'content-type: application/json',
                'Accept: application/json',
            ]
        ]);

        if ($response['code'] === 200) {
            return true;
        } else {
            return $response['errors'];
        }
    }

    /**
     * @param UserInterface $user
     * @return array|int
     * @throws Exception
     */
    public function createUser(UserInterface $user): array|int
    {
        $userDetails = [
            'firstname' => $user->getFirstname(),
            'lastname'  => $user->getLastname(),
            'email'     => $user->getMail(),
            'login'     => $user->getLogin()
        ];

        $response = CurlModel::exec([
            'url'        => rtrim($this->config->getUrl(), '/') . '/rest/users',
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'     => 'POST',
            'headers'    => [
                'content-type: application/json',
                'Accept: application/json',
            ],
            'body'       => json_encode($userDetails),
        ]);

        if ($response['code'] === 200) {
            return $response['response']['id'];
        } else {
            return $response['errors'] ??
                ['errors' => 'Error occurred during the creation of the Maarch Parapheur user.'];
        }
    }

    /**
     * @param UserInterface $user
     * @return array|bool
     * @throws Exception
     */
    public function updateUser(UserInterface $user): array|bool
    {
        $userDetails = [
            'firstname' => $user->getFirstname(),
            'lastname'  => $user->getLastname(),
            'email'     => $user->getMail(),
            "phone"     => $user->getPhone()
        ];
        $externalId = $user->getExternalId();

        $response = CurlModel::exec([
            'url'        => rtrim(
                $this->config->getUrl(),
                '/'
            ) . '/rest/users/' . $externalId['internalParapheur'],
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'     => 'PUT',
            'headers'    => [
                'content-type: application/json',
                'Accept: application/json',
            ],
            'body'       => json_encode($userDetails),
        ]);

        if ($response['code'] === 200) {
            return true;
        } else {
            return $response['errors'] ?? ['errors' => 'Failed to update the user in Maarch Parapheur.'];
        }
    }

    /**
     * @param UserInterface $user
     * @return array|bool
     * @throws Exception
     */
    public function deleteUser(UserInterface $user): array|bool
    {
        $externalId = $user->getExternalId();
        $response = CurlModel::exec([
            'url'        => rtrim(
                $this->config->getUrl(),
                '/'
            ) . '/rest/users/' . $externalId['internalParapheur'],
            'basicAuth' => [
                'user'     => $this->config->getUserWebService()->getLogin(),
                'password' => $this->config->getUserWebService()->getPassword(),
            ],
            'method'     => 'DELETE',
            'headers'    => [
                'content-type: application/json',
                'Accept: application/json',
            ]
        ]);
        if ($response['code'] === 200) {
            return true;
        } else {
            return $response['errors'] ?? ['errors' => 'Failed to delete the user in Maarch Parapheur.'];
        }
    }
}
