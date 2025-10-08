<?php

namespace MaarchCourrier\Tests\app\resource\Infrastructure;

use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use MaarchCourrier\Tests\CourrierTestCase;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use SrcCore\http\Response;

class EncryptedResourceTest extends CourrierTestCase
{
    private const UNENCRYPTED_DOCSERVER_ID = 2; // From data_fr.sql
    private const PATH_ENCRYPTED_TEMPLATE = '/tmp/unitTestMaarchCourrierEncrypted/';

    private static int $encryptedDocserverId;
    private static array $resourcesToDelete = [];

    protected function tearDown(): void
    {
        // Remove docserver created during test
        if (isset(self::$encryptedDocserverId)) {
            DocserverModel::delete(['id' => self::$encryptedDocserverId]);
            rmdir(self::PATH_ENCRYPTED_TEMPLATE);
        }

        // Unlock unencrypted docserver => set is_readonly to false
        DocserverModel::update([
            'set'   => ['is_readonly' => 'N'],
            'where' => ['id = ?'],
            'data'  => [self::UNENCRYPTED_DOCSERVER_ID]
        ]);

        // Remove resources created during test
        foreach (self::$resourcesToDelete as $resource) {
            ResModel::delete(['where' => ['res_id = ?'], 'data' => [$resource]]);
        }
    }

    public function testCheckIfEncryptedResourceFileIsLocatedInAEncryptedDocserver(): void
    {
        // Arrange
        $this->lockUnencryptedMainDocumentDocserver();
        self::$encryptedDocserverId = $this->createEncryptedDocserverForMainResourceDocument();
        $resId = $this->createResource();

        // Act
        $resController  = new ResController();
        $request        = $this->createRequest('GET');

        $response = $resController->getResourceFileInformation($request, new Response(), ['resId' => $resId]);
        $resourceInfo = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertIsArray($resourceInfo['information']);
        $this->assertNotEmpty($resourceInfo['information']);
        $this->assertSame('txt', $resourceInfo['information']['format']);
        $this->assertNotEmpty($resourceInfo['information']['docserverPathFile']);
        $this->assertIsString($resourceInfo['information']['docserverPathFile']);
        $this->assertStringContainsString(self::PATH_ENCRYPTED_TEMPLATE, $resourceInfo['information']['docserverPathFile']);
    }

    public function testCanRetrieveOriginalResourceContentFromEncryptedDocserver(): void
    {
        // Arrange
        $this->lockUnencryptedMainDocumentDocserver();
        self::$encryptedDocserverId = $this->createEncryptedDocserverForMainResourceDocument();
        $resId = $this->createResource();
        $expectedBase64 = $this->getSampleDocumentBase64();
        $actualBase64 = $this->getFileBase64OnDocserver($resId);

        // Act
        $resController  = new ResController();
        $request        = $this->createRequest('GET');
        $request = $request->withQueryParams(['mode' => 'base64']);

        $response = $resController->getOriginalFileContent($request, new Response(), ['resId' => $resId]);
        $resource = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertSame($expectedBase64, $resource['encodedDocument']);

        // If docserver is encrypted, the base64 of the file is different in docserver than the decrypted base64
        $this->assertNotSame($actualBase64, $resource['encodedDocument']);
    }

    private function lockUnencryptedMainDocumentDocserver(): void
    {
        DocserverModel::update([
            'set'   => ['is_readonly' => 'Y'],
            'where' => ['id = ?'],
            'data'  => [self::UNENCRYPTED_DOCSERVER_ID]
        ]);
    }

    private function createEncryptedDocserverForMainResourceDocument(): int
    {
        if (!is_dir(self::PATH_ENCRYPTED_TEMPLATE)) {
            mkdir(self::PATH_ENCRYPTED_TEMPLATE);
        }
        $args = [
            'docserver_id'      =>  'ENCRYPTED_FASTHD_MAN',
            'docserver_type_id' =>  'DOC',
            'device_label'      =>  'new encrypted docserver',
            'size_limit_number' =>  50000000000,
            'path_template'     =>  self::PATH_ENCRYPTED_TEMPLATE,
            'coll_id'           =>  'letterbox_coll',
            'is_readonly'       =>  false,
            'is_encrypted'      =>  true
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);
        $docserverController = new DocserverController();

        $response     = $docserverController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());

        return $responseBody->docserver;
    }

    private function createResource(): int
    {
        $previousLogin = $GLOBALS['login'];
        $this->connectAsUser('cchaplin');

        $fileContent = $this->getSampleDocumentBase64();

        $body = [
            'modelId'          => 1,
            'status'           => 'NEW',
            'encodedFile'      => $fileContent,
            'format'           => 'txt',
            'confidentiality'  => false,
            'documentDate'     => '2023-12-01 17:18:47',
            'arrivalDate'      => '2023-12-01 17:18:47',
            'processLimitDate' => '2033-12-01',
            'doctype'          => 102,
            'destination'      => 15,
            'initiator'        => 15,
            'subject'          => 'Breaking News : Superman is alive - PHP unit',
            'typist'           => 19,
            'priority'         => 'poiuytre1357nbvc',
            'senders'          => [['type' => 'contact', 'id' => 1], ['type' => 'user', 'id' => 21], ['type' => 'entity', 'id' => 1]],
        ];
        $fullRequest = $this->createRequestWithBody('POST', $body);
        $resController = new ResController();

        $response      = $resController->create($fullRequest, new Response());
        $responseBody  = json_decode((string)$response->getBody());

        $this->connectAsUser($previousLogin);

        self::$resourcesToDelete[] = $responseBody->resId;

        return $responseBody->resId;
    }

    private function getSampleDocumentBase64(): string
    {
        $fileContent = file_get_contents('test/unitTests/samples/test.txt');

        return base64_encode($fileContent);
    }

    private function getFileBase64OnDocserver(int $resId): string
    {
        $resController  = new ResController();
        $request        = $this->createRequest('GET');

        $response = $resController->getResourceFileInformation($request, new Response(), ['resId' => $resId]);
        $info = json_decode((string)$response->getBody(), true);

        $content = file_get_contents($info['information']['docserverPathFile']);

        return base64_encode($content);
    }
}
