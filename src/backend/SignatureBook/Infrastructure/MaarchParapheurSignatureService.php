<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MaarchParapheurSignatureService class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure;

use Exception;
use Firebase\JWT\JWT;
use MaarchCourrier\Core\Domain\Curl\CurlRequest;
use MaarchCourrier\Core\Infrastructure\Curl\CurlService;
use MaarchCourrier\SignatureBook\Domain\Port\SignatureServiceInterface;
use MaarchCourrier\SignatureBook\Domain\Problem\CurlRequestErrorProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookServiceConfig;
use SrcCore\controllers\UrlController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;

class MaarchParapheurSignatureService implements SignatureServiceInterface
{
    private SignatureBookServiceConfig $config;

    /**
     * @param SignatureBookServiceConfig $config
     * @return $this
     */
    public function setConfig(SignatureBookServiceConfig $config): MaarchParapheurSignatureService
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param int $documentId
     * @param string|null $hashSignature
     * @param array|null $signatures
     * @param string|null $certificate
     * @param string|null $signatureContentLength
     * @param string|null $signatureFieldName
     * @param string|null $tmpUniqueId
     * @param string $accessToken
     * @param string|null $cookieSession
     * @param array $resourceToSign
     * @return array|bool
     * @throws Exception
     */
    public function applySignature(
        int $documentId,
        ?string $hashSignature,
        ?array $signatures,
        ?string $certificate,
        ?string $signatureContentLength,
        ?string $signatureFieldName,
        ?string $tmpUniqueId,
        string $accessToken,
        ?string $cookieSession,
        array $resourceToSign
    ): array|bool {
        $payloadToken = $resourceToSign;
        $payloadToken['userSerialId'] = $GLOBALS['id'];

        $webhook = [
            'url'   => UrlController::getCoreUrl() . '/rest/signatureBook/webhook',
            'token' => JWT::encode($payloadToken, CoreConfigModel::getEncryptKey(), 'HS256')
        ];

        $response = CurlModel::exec([
            'url'        => rtrim($this->config->getUrl(), '/') . '/rest/documents/' . $documentId . '/actions/1',
            'bearerAuth' => ['token' => $accessToken],
            'headers'    => [
                'content-type: application/json',
                'Accept: application/json',
                'cookie: PHPSESSID=' . $cookieSession
            ],
            'method'     => 'PUT',
            'body'       => json_encode([
                'hashSignature'          => $hashSignature,
                'signatures'             => $signatures,
                'certificate'            => $certificate,
                'signatureContentLength' => $signatureContentLength,
                'signatureFieldName'     => $signatureFieldName,
                'tmpUniqueId'            => $tmpUniqueId,
                'webhook'                => $webhook
            ]),
        ]);

        if ($response['code'] >= 400 || $response['code'] < 200) {
            $errorMessage = $response['response']['errors'] ??
                $response['errors'] ??
                'Error occurred while applying the signature';

            $returnCurl['errors'] = $errorMessage;
            $returnCurl['context'] = $response['response']['context'] ?? null;

            return $returnCurl;
        }
        return true;
    }

    /**
     * @param string $accessToken
     * @param string $urlRetrieveDoc
     * @return array
     * @throws CurlRequestErrorProblem
     */
    public function retrieveDocumentSign(string $accessToken, string $urlRetrieveDoc): array
    {
        $curlRequest = new CurlRequest();
        $curlRequest = $curlRequest->createFromArray([
            'url'        => $urlRetrieveDoc,
            'method'     => 'GET',
            'authBearer' => $accessToken
        ]);

        $curlService = new CurlService();
        $curlRequest = $curlService->call($curlRequest);
        if ($curlRequest->getCurlResponse()->getHttpCode() >= 300) {
            throw new CurlRequestErrorProblem(
                $curlRequest->getCurlResponse()->getHttpCode(),
                $curlRequest->getCurlResponse()->getContentReturn()
            );
        }

        $curlResponseContent = $curlRequest->getCurlResponse()->getContentReturn();

        return ['response' => $curlResponseContent];
    }
}
