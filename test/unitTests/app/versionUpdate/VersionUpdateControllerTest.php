<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\versionUpdate;

use MaarchCourrier\Tests\CourrierTestCase;
use Slim\Routing\RouteContext;
use SrcCore\http\Response;
use Parameter\models\ParameterModel;
use SrcCore\models\CoreConfigModel;
use VersionUpdate\controllers\VersionUpdateController;
use VersionUpdate\middlewares\VersionUpdateMiddleware;
use MaarchCourrier\Tests\app\versionUpdate\VersionUpdateControllerMock;

class VersionUpdateControllerTest extends CourrierTestCase
{
    private static $backupDatabaseVersion = null;
    private static $filesToRemove = [];
    private static $availableTestFolder = null;
    private static $nextMigrationFolderPath = null;

    protected function setUp(): void
    {
        $parameter = ParameterModel::getById(['select' => ['param_value_string'], 'id' => 'database_version']);
        self::$backupDatabaseVersion = $parameter['param_value_string'];

        $packageJson = CoreConfigModel::getJsonLoaded(['path' => 'package.json']);
        $parts = explode('.', $packageJson['version']);
        // Get the last part and increment it
        $lastPart = end($parts);
        $lastPart = (int)$lastPart + 1;
        $parts[count($parts) - 1] = $lastPart;
        $nextVersion = implode('.', $parts);

        $sampleMigrationFolderPath = getcwd() . "/install/samples/migration/xxxx.x.x";
        self::$nextMigrationFolderPath = getcwd() . "/migration/$nextVersion";

        if (!file_exists(self::$nextMigrationFolderPath)) {
            mkdir(self::$nextMigrationFolderPath, 0777);
        }
        self::$availableTestFolder = "migration/$nextVersion";

        copy($sampleMigrationFolderPath . '/1-TestErrorTypoInUpdate.php', self::$nextMigrationFolderPath . '/1-TestErrorTypoInUpdate.php');
        copy($sampleMigrationFolderPath . '/2-TestErrorTypoInRollback.php', self::$nextMigrationFolderPath . '/2-TestErrorTypoInRollback.php');
        copy($sampleMigrationFolderPath . '/xxxx.x.x.sql', self::$nextMigrationFolderPath . "/$nextVersion.sql");

        // For tearDown
        self::$filesToRemove = [
            self::$nextMigrationFolderPath . '/1-TestErrorTypoInUpdate.php',
            self::$nextMigrationFolderPath . '/2-TestErrorTypoInRollback.php',
            self::$nextMigrationFolderPath . "/$nextVersion.sql",
            self::$nextMigrationFolderPath
        ];
    }

    public function testGet()
    {
        $versionUpdateController = new VersionUpdateController();

        //  GET
        $request = $this->createRequest('GET');
        $response       = $versionUpdateController->get($request, new Response());
        $responseBody   = json_decode((string)$response->getBody());
        $this->assertIsString($responseBody->currentVersion);
        $this->assertNotNull($responseBody->currentVersion);
        $this->assertMatchesRegularExpression('/^\d{4}\.\d\.\d+$/', $responseBody->currentVersion, 'Invalid current version');

        if ($responseBody->lastAvailableMinorVersion != null) {
            $this->assertIsString($responseBody->lastAvailableMinorVersion);
            $this->assertMatchesRegularExpression('/^\d{4}\.\d\.\d+$/', $responseBody->lastAvailableMinorVersion, 'Invalid available minor version');
        }

        if ($responseBody->lastAvailableMajorVersion != null) {
            $this->assertIsString($responseBody->lastAvailableMajorVersion);
            $this->assertMatchesRegularExpression('/^\d{4}\.\d\.\d+$/', $responseBody->lastAvailableMajorVersion, 'Invalid available major version');
        }
    }

    public function apiRouteProvideEmptyResponseDataForRoutesWithoutMigration(): array
    {
        $return = [];

        foreach (VersionUpdateController::ROUTES_WITHOUT_MIGRATION as $methodeAndRoute) {
            $return[$methodeAndRoute] = [
                'input' => [
                    'currentMethod' => explode('/',$methodeAndRoute)[0],
                    'currentRoute'  => '/' . explode('/',$methodeAndRoute)[1]
                ]
            ];
        }

        return $return;
    }

    public function apiRouteProvideResponseDataForRoutesWithMigration(): array
    {
        $return = [];
        $routes = [
            'GET/versionsUpdateSQL',
            'GET/validUrl',
            'POST/authenticate',
            'GET/authenticate/token',
            'PUT/actions/{id}',
            'POST/convertedFile'
        ];

        foreach ($routes as $methodeAndRoute) {
            $return[$methodeAndRoute] = [
                'input' => [
                    'currentMethod' => explode('/',$methodeAndRoute)[0],
                    'currentRoute'  => '/' . explode('/',$methodeAndRoute)[1]
                ]
            ];
        }

        return $return;
    }

    /**
     * @dataProvider apiRouteProvideEmptyResponseDataForRoutesWithoutMigration
     */
    public function testMiddlewareControlExpectingEmptyResponseUsingApiRoute($input)
    {
        $control = VersionUpdateMiddleware::middlewareControl($input['currentMethod'], $input['currentRoute']);

        $this->assertEmpty($control);
        $this->assertSame([], $control);
    }

    /**
     * @dataProvider apiRouteProvideResponseDataForRoutesWithMigration
     */
    public function testMiddlewareControlExpectingResponseUsingApiRoute($input)
    {
        VersionUpdateControllerMock::isMigrating();

        $control = VersionUpdateMiddleware::middlewareControl($input['currentMethod'], $input['currentRoute']);

        $this->assertNotEmpty($control);
        $this->assertNotEmpty($control['response']);
        $this->assertSame([
            "errors"        => "Service unavailable : migration in progress",
            "lang"          => "migrationProcessing",
            'migrating'     => true
        ], $control['response']);
    }

    public function testAutoUpdateLauncherWithSomethingToMigrateExpectSucessCode200AndDatabaseUpdated()
    {
        // Arrange
        $versionUpdateController = new VersionUpdateController();

        // Act
        // PUT
        $request  = $this->createRequest('PUT');
        $response = $versionUpdateController->autoUpdateLauncher($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        // Assert
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($responseBody->success ?? null);
        $this->assertIsString($responseBody->success ?? null);
        $this->assertSame('Database has been updated', $responseBody->success);
    }

    public function testAutoUpdateLauncherWithNothingToMigrateExpectSucessCode204()
    {
        // Arrange
        $versionUpdateController = new VersionUpdateController();

        $folderTags = scandir(getcwd() . "/migration");
        natcasesort($folderTags);
        $lastTag = $folderTags[count($folderTags) - 1];
        ParameterModel::update(['id' => "database_version", 'param_value_string' => $lastTag]);

        // Act
        // PUT
        $request  = $this->createRequest('PUT');
        $response = $versionUpdateController->autoUpdateLauncher($request, new Response());

        // Assert
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testAvailableFoldersWithSuccess()
    {
        // Arrange
        $availableFolders = VersionUpdateController::getAvailableFolders();
        // Act

        // Assert
        $this->assertNotEmpty($availableFolders);
        $this->assertEmpty($availableFolders['errors'] ?? null);
        $this->assertNotEmpty($availableFolders['folders'] ?? []);
        $this->assertContains(self::$availableTestFolder, $availableFolders['folders']);
    }

    public function provideDatabaseVersionFormats()
    {
        return [
            '2301.1 Format' => [
                'input' => '2301.1'
            ],
            '2301.1.4.1 Format' => [
                'input' => '2301.1.4.1'
            ]
        ];
    }

    /**
     * @dataProvider provideDatabaseVersionFormats
     */
    public function testAvailableFoldersWithDatabaseVersionFormatThatDoesNotHaveThreeDotsExpectBadFormatDatabaseVersion($input)
    {
        // Arrange
        ParameterModel::update(['id' => "database_version", 'param_value_string' => $input]);

        // Act
        $availableFolders = VersionUpdateController::getAvailableFolders();

        // Assert
        $this->assertNotEmpty($availableFolders);
        $this->assertEmpty($availableFolders['folders'] ?? []);
        $this->assertNotEmpty($availableFolders['errors']);
        $this->assertSame($availableFolders['errors'], "Bad format database_version");
    }

    public function testAvailableFoldersWithAvailableFolderIsEmptyExpectError()
    {
        // Arrange
        foreach (self::$filesToRemove as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        // Act
        $availableFolders = VersionUpdateController::getAvailableFolders();

        // Assert
        $this->assertNotEmpty($availableFolders);
        $this->assertEmpty($availableFolders['folders'] ?? []);
        $this->assertNotEmpty($availableFolders['errors']);
        $this->assertSame($availableFolders['errors'], "Folder '" . self::$availableTestFolder . "' is empty, no updates are found!");
    }

    public function testExecuteTagFolderFilesWithTagFolderListIsEmpty()
    {
        $throwableMessage = null;
        try {
            VersionUpdateController::executeTagFolderFiles([]);
        } catch (\Throwable $th) {
            $throwableMessage = $th->getMessage();
        }

        $this->assertNotNull($throwableMessage);
        $this->assertIsString($throwableMessage);
        $this->assertSame('$tagFolderList must be a non empty array of type string', $throwableMessage);
    }

    public function testExecuteTagSqlFileWithSuccess()
    {
        // Arrange
        $sqlFilePath = "/tmp/tmpVersionUpdateSqlFile.sql";
        $docserverMigrationFolderPath = '/tmp';
        $content = "--DATABASE_BACKUP|parameters\n
        UPDATE parameters SET param_value_string = '2301.2.0' WHERE id = 'database_version';";
        file_put_contents($sqlFilePath, $content);

        // Act
        $result = VersionUpdateController::executeTagSqlFile($sqlFilePath, $docserverMigrationFolderPath);

        // Assert
        $this->assertSame(true, $result);
    }

    public function testExecuteTagSqlFileWithSqlFilePathIsEmpty()
    {
        $throwableMessage = null;
        try {
            VersionUpdateController::executeTagSqlFile('', 'docserver folder path');
        } catch (\Throwable $th) {
            $throwableMessage = $th->getMessage();
        }

        $this->assertNotNull($throwableMessage);
        $this->assertIsString($throwableMessage);
        $this->assertSame('$sqlFilePath must be a non empty string', $throwableMessage);
    }

    public function testExecuteTagSqlFileWithDocserverMigrationFolderPathIsEmpty()
    {
        $throwableMessage = null;
        try {
            VersionUpdateController::executeTagSqlFile('sql file path', '');
        } catch (\Throwable $th) {
            $throwableMessage = $th->getMessage();
        }

        $this->assertNotNull($throwableMessage);
        $this->assertIsString($throwableMessage);
        $this->assertSame('$docserverMigrationFolderPath must be a non empty string', $throwableMessage);
    }

    public function testExecuteTagSqlFileWithTableDoesNotExistExpectPostgresqlDumpToFailed()
    {
        // Arrange
        $sqlFilePath = "/tmp/tmpVersionUpdateSqlFile.sql";
        $docserverMigrationFolderPath = '/tmp';
        $content = "--DATABASE_BACKUP|parameters_\n
        UPDATE parameters SET param_value_string = '2301.2.0' WHERE id = 'database_version';";
        file_put_contents($sqlFilePath, $content);

        // Act
        $result = VersionUpdateController::executeTagSqlFile($sqlFilePath, $docserverMigrationFolderPath);

        // Assert
        $this->assertSame(false, $result);
    }

    public function testExecuteTagSqlFileWithBackupPathIsNotReachableExpectPostgresqlDumpToFailed()
    {
        // Arrange
        $sqlFilePath = "/tmp/tmpVersionUpdateSqlFile.sql";
        $docserverMigrationFolderPath = '/tmp/someTestFolder';
        $content = "--DATABASE_BACKUP|parameters\n
        UPDATE parameters SET param_value_string = '2301.2.0' WHERE id = 'database_version';";
        file_put_contents($sqlFilePath, $content);

        // Act
        $result = VersionUpdateController::executeTagSqlFile($sqlFilePath, $docserverMigrationFolderPath);

        // Assert
        $this->assertSame(false, $result);
    }

    protected function tearDown(): void
    {
        if (file_exists(VersionUpdateController::UPDATE_LOCK_FILE)) {
            unlink(VersionUpdateController::UPDATE_LOCK_FILE);
        }

        if (file_exists(self::$nextMigrationFolderPath)) {
            chmod(self::$nextMigrationFolderPath, 0777);
        }

        foreach (self::$filesToRemove as $path) {
            if (file_exists($path)) {
                if (is_dir($path)){
                    rmdir($path);
                } else {
                    unlink($path);
                }
            }
        }
        ParameterModel::delete(['id' => "maarch_courrier_test"]);
        ParameterModel::update(['id' => "database_version", 'param_value_string' => self::$backupDatabaseVersion]);
    }
}
