<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\docserver;

use Docserver\controllers\DocserverController;
use Docserver\controllers\DocserverTypeController;
use Docserver\models\DocserverModel;
use Parameter\models\ParameterModel;
use SrcCore\http\Response;
use MaarchCourrier\Tests\CourrierTestCase;
use SrcCore\models\CoreConfigModel;

class DocserverControllerTest extends CourrierTestCase
{
    private static $id = null;
    private static $pathTemplate = '/tmp/unitTestMaarchCourrier/';
    private static $docserver = [];
    private static ?string $generalConfigPath = null;
    private static $generalConfigOriginal = null;

    public function testGet()
    {
        $docserverController = new DocserverController();

        $request = $this->createRequest('GET');

        $response = $docserverController->get($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->docservers);
        $this->assertNotEmpty($responseBody->types);
        $this->assertFalse(property_exists($responseBody, 'docserverEncryptionStatus'));
    }

    public function testCreate()
    {
        $docserverController = new DocserverController();

        //  CREATE
        if (!is_dir(self::$pathTemplate)) {
            mkdir(self::$pathTemplate);
        }

        $args = [
            'docserver_id'      => 'NEW_DOCSERVER',
            'docserver_type_id' => 'DOC',
            'device_label'      => 'new docserver',
            'size_limit_number' => 50000000000,
            'path_template'     => self::$pathTemplate,
            'coll_id'           => 'letterbox_coll',
            'is_encrypted'      => false
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $docserverController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());

        self::$id = $responseBody->docserver;
        $this->assertIsInt(self::$id);

        //  READ
        $request = $this->createRequest('GET');
        $response = $docserverController->getById($request, new Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('NEW_DOCSERVER', $responseBody->docserver_id);

        //  CREATE
        $args = [
            'docserver_id'      => 'WRONG_PATH',
            'docserver_type_id' => 'DOC',
            'device_label'      => 'new docserver',
            'size_limit_number' => 50000000000,
            'path_template'     => '/wrong/path/',
            'coll_id'           => 'letterbox_coll',
            'is_encrypted'      => false
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);

        $response = $docserverController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(_PATH_OF_DOCSERVER_UNAPPROACHABLE, $responseBody->errors);

        //  CREATE
        $args = [
            'docserver_id'      => 'BAD_REQUEST',
            'docserver_type_id' => 'DOC',
            'device_label'      => 'new docserver',
            'size_limit_number' => 50000000000,
            'path_template'     => null,
            'coll_id'           => 'letterbox_coll',
            'is_encrypted'      => false
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);
        $response = $docserverController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Bad Request', $responseBody->errors);

        //  CREATE
        $args = [
            'docserver_id'      => 'NEW_DOCSERVER',
            'docserver_type_id' => 'DOC',
            'device_label'      => 'new docserver',
            'size_limit_number' => 50000000000,
            'path_template'     => '/var/docserversDEV/dev1804/archive_transfer/',
            'coll_id'           => 'letterbox_coll',
            'is_encrypted'      => false
        ];
        $fullRequest = $this->createRequestWithBody('POST', $args);
        $response = $docserverController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(_ID . ' ' . _ALREADY_EXISTS, $responseBody->errors);
    }

    public function testUpdate()
    {
        $docserverController = new DocserverController();

        //  UPDATE
        $args = [
            'docserver_type_id' => 'DOC',
            'device_label'      => 'updated docserver',
            'size_limit_number' => 50000000000,
            'path_template'     => self::$pathTemplate,
            'is_readonly'       => true,
            'is_encrypted'      => false
        ];
        $fullRequest = $this->createRequestWithBody('PUT', $args);
        $response = $docserverController->update($fullRequest, new Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->docserver);
        $this->assertSame('updated docserver', $responseBody->docserver->device_label);

        //  READ
        $request = $this->createRequest('GET');
        $response = $docserverController->getById($request, new Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('updated docserver', $responseBody->device_label);

        //  UPDATE
        $args = [
            'docserver_type_id' => 'DOC',
            'device_label'      => 'updated docserver',
            'size_limit_number' => 50000000000,
            'path_template'     => '/wrong/path/',
            'is_readonly'       => true,
            'is_encrypted'      => false
        ];
        $fullRequest = $this->createRequestWithBody('PUT', $args);
        $response = $docserverController->update($fullRequest, new Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(_PATH_OF_DOCSERVER_UNAPPROACHABLE, $responseBody->errors);

        //  UPDATE
        $args = [
            'docserver_type_id' => 'DOC',
            'device_label'      => 'updated docserver',
            'size_limit_number' => 50000000000,
            'path_template'     => self::$pathTemplate,
            'is_readonly'       => true,
            'is_encrypted'      => false
        ];
        $fullRequest = $this->createRequestWithBody('PUT', $args);
        $response = $docserverController->update($fullRequest, new Response(), ['id' => 12345]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Docserver not found', $responseBody->errors);
    }

    public function testDelete()
    {
        $docserverController = new DocserverController();

        //  DELETE
        $request = $this->createRequest('DELETE');
        $response = $docserverController->delete($request, new Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertsame('success', $responseBody->success);

        //  READ
        $request = $this->createRequest('GET');
        $response = $docserverController->getById($request, new Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Docserver not found', $responseBody->errors);

        //  DELETE
        $request = $this->createRequest('DELETE');
        $response = $docserverController->delete($request, new Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Docserver does not exist', $responseBody->errors);

        rmdir(self::$pathTemplate);
    }

    public function testGetDocserverTypes()
    {
        $docserverTypeController = new DocserverTypeController();

        $request = $this->createRequest('GET');

        $response = $docserverTypeController->get($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->docserverTypes);
        foreach ($responseBody->docserverTypes as $docserverType) {
            $this->assertNotEmpty($docserverType->docserver_type_id);
            $this->assertNotEmpty($docserverType->docserver_type_label);
            $this->assertNotEmpty($docserverType->enabled);
        }
    }
//TODO  Uncomment the test when else condition in getMigrationFolderPath() is removed
//    public function testWhenTheMigrationFolderDoesNotExistAnErrorIsReturned(): void
//    {
//        //Arrange
//        DatabaseModel::delete([
//            'table'=> 'docservers',
//            'where'=> ["docserver_id = 'MIGRATION'"]
//        ]);
//        //Arrange
//        DatabaseModel::delete([
//            'table'=> 'docservers',
//            'where'=> ["docserver_id = 'FASTHD_MAN'"]
//        ]);
//
//        //Act
//        $migrationFolder = DocserverController::getMigrationFolderPath();
//
//        //Assert
//        $this->assertSame('Docserver migration does not exist', $migrationFolder['errors']);
//    }

    public function testWhenThePathTemplateOfTheMigrationFolderDoesNotExistAnErrorIsReturned(): void
    {
        //Arrange
        DocserverModel::update([
            'table' => 'docservers',
            'set'   => [
                'path_template' => ''
            ],
            'where' => ['docserver_id = ?', 'coll_id = ?'],
            'data'  => [self::$docserver['docserver_id'], self::$docserver['coll_id']]
        ]);
        //Act
        $migrationFolder = DocserverController::getMigrationFolderPath();

        //Assert
        $this->assertSame('Docserver path is empty', $migrationFolder['errors']);
    }

    public function testMigrationFolderExistAndTheTemplatePathIsCorrect(): void
    {
        //Arrange

        //Act
        $migrationFolder = DocserverController::getMigrationFolderPath();
        //assert
        $this->assertSame('/opt/maarch/docservers/migration/', $migrationFolder['path']);
    }

    public function testGetDocserverEncryptionStatusWhenSpecifyingParameterWithoutEnablingInConfiguration(): void
    {
        $docserverController = new DocserverController();

        $request        = $this->createRequest('GET');
        $request        = $request->withQueryParams(['getEncryptionStatus' => true]);
        $response       = $docserverController->get($request, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->docservers);
        $this->assertNotEmpty($responseBody->types);
        $this->assertTrue(property_exists($responseBody, 'docserverEncryptionStatus'));
        $this->assertFalse($responseBody->docserverEncryptionStatus);
    }

    public function testGetDocserverEncryptionStatusWhenSpecifyingParameterAndEnablingInConfiguration()
    {
        // enableDocserverEncryption in config...
        $config = self::$generalConfigOriginal;
        $config['config']['enableDocserverEncryption'] = true;
        file_put_contents(self::$generalConfigPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $docserverController = new DocserverController();

        $request        = $this->createRequest('GET');
        $request        = $request->withQueryParams(['getEncryptionStatus' => true]);
        $response       = $docserverController->get($request, new Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->docservers);
        $this->assertNotEmpty($responseBody->types);
        $this->assertTrue(property_exists($responseBody, 'docserverEncryptionStatus'));
        $this->assertTrue($responseBody->docserverEncryptionStatus);
    }

    protected function setUp(): void
    {
        self::$docserver = DocserverModel::getCurrentDocserver(['typeId' => 'MIGRATION', 'collId' => 'migration', 'select' => ['*']]);
        self::$generalConfigPath = (file_exists("config/config.json") ? "config/config.json" : "config/config.json.default");
        self::$generalConfigOriginal = json_decode(file_get_contents(self::$generalConfigPath), true);
    }

    protected function tearDown(): void
    {
        $docservers = DocserverModel::getCurrentDocserver(['typeId' => 'MIGRATION', 'collId' => 'migration', 'select' => ['path_template']]);
        if (empty($docservers)) {
            DocserverModel::create(self::$docserver);
        } else {
            DocserverModel::update([
                'table' => 'docservers',
                'set'   => [
                    'path_template' => self::$docserver['path_template']
                ],
                'where' => ['docserver_id = ?', 'coll_id = ?'],
                'data'  => [self::$docserver['docserver_id'], self::$docserver['coll_id']]
            ]);
        }


        ParameterModel::delete(['id' => 'last_docservers_size_calculation']);
        $tmpPath = CoreConfigModel::getTmpPath();
        $lockFile = $tmpPath . DIRECTORY_SEPARATOR . 'calculateDocserversSize.lck';
        if (is_file($lockFile)) {
            unlink($lockFile);
        }
        $this->connectAsUser('superadmin');

        file_put_contents(self::$generalConfigPath, json_encode(self::$generalConfigOriginal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    }

    private function setDateSizeCalculation($date = null): void
    {
        ParameterModel::create(['id' => 'last_docservers_size_calculation', 'param_value_date' => $date ?? date('Y-m-d H:i:s')]);
    }

    private function getDateSizeCalculation(): array
    {
        return ParameterModel::getById(['id' => 'last_docservers_size_calculation']);
    }

    private function createLockFile(): void
    {
        $tmpPath = CoreConfigModel::getTmpPath();
        $lockFile = $tmpPath . DIRECTORY_SEPARATOR . 'calculateDocserversSize.lck';
        file_put_contents($lockFile, "locked");
    }

    public function testCanCalculateDocserversSizeNonExistantDateLastCalculation(): void
    {
        $docserverController = new DocserverController();

        //Act
        $request = $this->createRequest('POST');
        $response = $docserverController->calculateSize($request, new Response());
        //assert

        $this->assertSame(204, $response->getStatusCode());
        $this->assertNotEmpty($this->getDateSizeCalculation());
    }

    public function testCanCalculateDocserversSizeExistantDateLastCalculation(): void
    {
        $docserverController = new DocserverController();

        //Arrange : Initialisation de la date de dernier calcul à une date ancienne
        $this->setDateSizeCalculation('2024-01-01 00:00:00');

        //Act
        $request = $this->createRequest('POST');
        $response = $docserverController->calculateSize($request, new Response());

        //assert
        $this->assertSame(204, $response->getStatusCode());
        $this->assertNotEmpty($this->getDateSizeCalculation());
    }

    public function testCannotCalculateDocserversSizeBecauseServiceForbidden(): void
    {
        $docserverController = new DocserverController();

        //Arrange : Connexion avec un utilisateur n'ayant pas les droits
        $this->connectAsUser('bblier');

        //Act
        $request = $this->createRequest('POST');
        $response = $docserverController->calculateSize($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        //assert
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Service forbidden', $responseBody->error);
    }

    public function testCannotCalculateDocserversSizeBecauseTooEarly(): void
    {
        $docserverController = new DocserverController();

        //Arrange : Initialisation de la date de dernier calcul à la date du jour
        $this->setDateSizeCalculation();

        //Act
        $request = $this->createRequest('POST');
        $response = $docserverController->calculateSize($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        //assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Last calculation is too early', $responseBody->error);
    }

    public function testCannotCalculateDocserversSizeBecauseLocked(): void
    {
        $docserverController = new DocserverController();

        //Arrange : Créer fichier lock
        $this->createLockFile();

        //Act
        $request = $this->createRequest('POST');
        $response = $docserverController->calculateSize($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        //assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Process already running', $responseBody->error);
    }
}
