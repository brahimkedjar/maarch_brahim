<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Version Update Controller
 * @author dev@maarch.org
 */

namespace VersionUpdate\controllers;

use Docserver\controllers\DocserverController;
use Exception;
use Gitlab\Client;
use Group\controllers\PrivilegeController;
use Parameter\models\ParameterModel;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use History\controllers\HistoryController;
use Throwable;
use User\models\UserModel;
use SrcCore\controllers\LogsController;
use SrcCore\interfaces\AutoUpdateInterface;

class VersionUpdateController
{
    public const UPDATE_LOCK_FILE = "migration/updating.lck";
    public const ROUTES_WITHOUT_MIGRATION = ['GET/languages/{lang}', 'GET/authenticationInformations', 'GET/images'];

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function get(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_update_control', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $client = new Client();
        $client->setUrl('https://labs.maarch.org/api/v4/');
        try {
            $tags = $client->tags()->all('12');
        } catch (Exception $e) {
            return $response->withJson(['errors' => $e->getMessage()]);
        }

        $applicationVersion = CoreConfigModel::getApplicationVersion();

        if (empty($applicationVersion)) {
            return $response->withStatus(400)->withJson(['errors' => "Can't load package.json"]);
        }

        $currentVersion = $applicationVersion;
        $versions = explode('.', $currentVersion);

        if (count($versions) < 3) {
            return $response->withStatus(400)->withJson(['errors' => "Bad tag format : {$applicationVersion}"]);
        } elseif (strlen($versions[0]) !== 4) {
            return $response->withStatus(400)->withJson(['errors' => "Bad tag format : {$applicationVersion}"]);
        }

        $currentMajorVersionTag = $versions[0];
        $currentMinorVersionTag = $versions[1];
        $currentPatchVersionTag = $versions[2];

        $availableMajorVersions = [];
        $availableMinorVersions = [];
        $availablePatchVersions = [];

        foreach ($tags as $value) {
            if (!preg_match("/^\d{4}\.\d\.\d+$/", $value['name'])) {
                continue;
            }
            $explodedValue = explode('.', $value['name']);

            $majorVersionTag = $explodedValue[0];
            $minorVersionTag = $explodedValue[1];
            $patchVersionTag = $explodedValue[2];

            if ($majorVersionTag > $currentMajorVersionTag) {
                $availableMajorVersions[] = $value['name'];
            } elseif ($majorVersionTag == $currentMajorVersionTag && $minorVersionTag > $currentMinorVersionTag) {
                $availableMinorVersions[] = $value['name'];
            } elseif ($minorVersionTag == $currentMinorVersionTag && $patchVersionTag > $currentPatchVersionTag) {
                $availablePatchVersions[] = $value['name'];
            }
        }

        natcasesort($availableMajorVersions);
        natcasesort($availableMinorVersions);
        natcasesort($availablePatchVersions);

        if (empty($availableMajorVersions)) {
            $lastAvailableMajorVersion = null;
        } else {
            $lastAvailableMajorVersion = end($availableMajorVersions);
        }

        if (empty($availableMinorVersions)) {
            $lastAvailableMinorVersion = null;
        } else {
            $lastAvailableMinorVersion = end($availableMinorVersions);
        }

        if (empty($availablePatchVersions)) {
            $lastAvailablePatchVersion = null;
        } else {
            $lastAvailablePatchVersion = end($availablePatchVersions);
        }

        $output = [];

        exec('git status --porcelain --untracked-files=no 2>&1', $output);

        return $response->withJson([
            'lastAvailableMajorVersion' => $lastAvailableMajorVersion,
            'lastAvailableMinorVersion' => $lastAvailableMinorVersion,
            'lastAvailablePatchVersion' => $lastAvailablePatchVersion,
            'currentVersion'            => $currentVersion,
            'canUpdate'                 => empty($output),
            'diffOutput'                => $output
        ]);
    }


    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @throws Exception
     * @codeCoverageIgnore
     */
    public function update(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_update_control', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $targetTag = $body['tag'];
        $targetTagVersions = explode('.', $targetTag);

        if (count($targetTagVersions) < 3) {
            return $response->withStatus(400)->withJson(['errors' => "Bad tag format : {$body['tag']}"]);
        }

        $targetMajorVersionTag = (int)$targetTagVersions[0];
        $targetMinorVersionTag = (int)$targetTagVersions[1];
        $targetPatchVersionTag = (int)$targetTagVersions[2];

        $applicationVersion = CoreConfigModel::getApplicationVersion();
        if (empty($applicationVersion)) {
            return $response->withStatus(400)->withJson(['errors' => "Can't load package.json"]);
        }

        $currentVersion = $applicationVersion;

        $versions = explode('.', $currentVersion);
        $currentMajorVersionTag = (int)$versions[0];
        $currentMinorVersionTag = (int)$versions[1];
        $currentPatchVersionTag = (int)$versions[2];

        if (
            $targetMajorVersionTag < $currentMajorVersionTag
        ) {
            return $response->withStatus(400)->withJson(['errors' => "Can't update to previous / same major tag"]);
        } elseif (
            $targetMajorVersionTag == $currentMajorVersionTag &&
            $targetMinorVersionTag < $currentMinorVersionTag
        ) {
            return $response->withStatus(400)->withJson(['errors' => "Can't update to previous / same minor tag"]);
        } elseif (
            $targetMajorVersionTag == $currentMajorVersionTag &&
            $targetMinorVersionTag == $currentMinorVersionTag &&
            $targetPatchVersionTag < $currentPatchVersionTag
        ) {
            return $response->withStatus(400)->withJson(['errors' => "Can't update to previous / same patch tag"]);
        }

        $output = [];
        exec('git status --porcelain --untracked-files=no 2>&1', $output);
        if (!empty($output)) {
            return $response->withStatus(400)->withJson([
                'errors' => 'Some files are modified. Can not update application',
                'lang'   => 'canNotUpdateApplication'
            ]);
        }

        try {
            $migrationTagFolderPath = VersionUpdateController::getMigrationTagFolderPath($targetTag);
        } catch (Throwable $th) {
            return $response->withStatus(400)->withJson(['errors' => $th->getMessage()]);
        }

        $actualTime = date("dmY-His");

        $output = [];
        exec('git fetch');
        exec("git checkout {$targetTag} 2>&1", $output, $returnCode);

        $log = "Application tag update from {$currentVersion} to {$targetTag}\nCheckout response {$returnCode} => " .
            implode(' ', $output) . "\n";
        file_put_contents(
            "{$migrationTagFolderPath}/updateVersion_{$actualTime}.log",
            $log,
            FILE_APPEND
        );

        if ($returnCode != 0) {
            return $response->withStatus(400)->withJson([
                'errors' => "Application tag update failed. Please check updateVersion.log at {$migrationTagFolderPath}"
            ]);
        }

        HistoryController::add([
            'tableName' => 'none',
            'recordId'  => $targetTag,
            'eventType' => 'UP',
            'userId'    => $GLOBALS['id'],
            'info'      => _APP_UPDATED_TO_TAG . ' : ' . $targetTag,
            'moduleId'  => null,
            'eventId'   => 'appUpdate',
        ]);

        return $response->withStatus(204);
    }

    /**
     * @return bool
     */
    public static function isMigrating(): bool
    {
        return file_exists(VersionUpdateController::UPDATE_LOCK_FILE);
    }

    /**
     * Get the migration tag folder path. Create the path if does not exist.
     * @param string $tagVersion
     * @return  string      Return the path of the migration tag folder
     * @throws  Exception   If an occurred from DocserverController::getMigrationFolderPath()
     */
    public static function getMigrationTagFolderPath(string $tagVersion): string
    {
        if (empty($tagVersion)) {
            throw new Exception('$tagVersion must be a non empty string');
        }

        $migrationFolder = DocserverController::getMigrationFolderPath();

        if (!empty($migrationFolder['errors'])) {
            throw new Exception($migrationFolder['errors']);
        }
        $migrationTagFolderPath = $migrationFolder['path'] . '/' . $tagVersion;

        if (!is_dir($migrationTagFolderPath)) {
            mkdir($migrationTagFolderPath, 0755, true);
        }

        return $migrationTagFolderPath;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public static function autoUpdateLauncher(Request $request, Response $response): Response
    {
        $availableFolders = VersionUpdateController::getAvailableFolders();
        if (!empty($availableFolders['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $availableFolders['errors']]);
        }

        if (empty($GLOBALS['id'] ?? null)) {
            $user = UserModel::get([
                'select' => ['id'],
                'where'  => ['mode = ? OR mode = ?'],
                'data'   => ['root_visible', 'root_invisible'],
                'limit'  => 1
            ]);
            $GLOBALS['id'] = $user[0]['id'];
        }

        if (!empty($availableFolders['folders'])) {
            try {
                VersionUpdateController::executeTagFolderFiles($availableFolders['folders']);
            } catch (Throwable $th) {
                return $response->withStatus(400)->withJson(['errors' => $th->getMessage()]);
            }
            return $response->withJson(['success' => 'Database has been updated']);
        }

        return $response->withStatus(204);
    }

    /**
     * Get any tag folders that are superior than the current database version
     * @param string $migrationFolderPath The location of migration folder
     * @return  array   Return 'errors' for unexpected errors | Return 'folders' with the list of folders
     */
    public static function getAvailableFolders(string $migrationFolderPath = 'migration'): array
    {
        $parameter = ParameterModel::getById(['select' => ['param_value_string'], 'id' => 'database_version']);

        $parameter = explode('.', $parameter['param_value_string']);

        if (count($parameter) != 3) {
            return ['errors' => "Bad format database_version"];
        }

        $dbMajorVersion = (int)$parameter[0];
        $dbMinorVersion = (int)$parameter[1];
        $dbPatchVersion = (int)$parameter[2];

        $folderTags = array_diff(scandir($migrationFolderPath), array('..', '.', '.gitkeep'));
        natcasesort($folderTags);
        $availableFolders = [];

        foreach ($folderTags as $folder) {
            $folderVersions = explode('.', $folder);
            $folderMajorVersion = (int)$folderVersions[0];
            $folderMinorVersion = (int)$folderVersions[1];
            $folderPatchVersion = (int)$folderVersions[2];

            if (
                $folderMajorVersion > $dbMajorVersion ||
                ($folderMajorVersion == $dbMajorVersion && $folderMinorVersion > $dbMinorVersion) ||
                (
                    $folderMajorVersion == $dbMajorVersion &&
                    $folderMinorVersion == $dbMinorVersion &&
                    $folderPatchVersion > $dbPatchVersion
                )
            ) {
                if (is_dir("$migrationFolderPath/$folder")) {
                    if (!is_readable("$migrationFolderPath/$folder")) {
                        return ['errors' => "Folder '$migrationFolderPath/$folder' is not readable"];
                    }
                    if (count(array_diff(scandir("$migrationFolderPath/$folder"), ['.', '..'])) == 0) {
                        return ['errors' => "Folder '$migrationFolderPath/$folder' is empty, no updates are found!"];
                    }
                    $availableFolders[] = "$migrationFolderPath/$folder";
                }
            }
        }

        return ['folders' => $availableFolders];
    }

    /**
     * Central function to run different types of files. SQL or PHP
     * @param string[] $tagFolderList A list of strings
     * @return  true        Return true when successful
     * @throws  Exception
     */
    public static function executeTagFolderFiles(array $tagFolderList): bool
    {
        if (empty($tagFolderList)) {
            throw new Exception('$tagFolderList must be a non empty array of type string');
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'Version Update Controller',
            'eventId'   => "Update",
            'level'     => 'INFO',
            'eventType' => "Beginning of the update..."
        ]);

        foreach ($tagFolderList as $tagFolder) {
            $tagVersion = basename($tagFolder);
            $tagFoldersFiles = scandir($tagFolder);

            if (in_array($tagFoldersFiles, ['.gitkeep', '.', '..'])) {
                continue;
            }

            try {
                $migrationTagFolderPath = VersionUpdateController::getMigrationTagFolderPath($tagFolder);
            } catch (Throwable $th) {
                throw new Exception($th->getMessage());
            }

            $sqlFilePath = "$tagFolder/$tagVersion.sql";
            $check = VersionUpdateController::executeTagSqlFile($sqlFilePath, $migrationTagFolderPath);
            if (empty($check)) {
                continue;
            }

            $sqlFileIndex = array_search("$tagVersion.sql", $tagFoldersFiles);
            if ($sqlFileIndex !== false) {
                unset($tagFoldersFiles[$sqlFileIndex]);
            }

            $runScriptsByTag = VersionUpdateController::runScriptsByTag($tagFoldersFiles, $tagVersion);

            ParameterModel::update(['id' => "database_version", 'param_value_string' => $tagVersion]);

            $info = "Result of {$runScriptsByTag['numberOfFiles']} migration files," .
                " success : {$runScriptsByTag['success']}," .
                " errors : {$runScriptsByTag['errors']}, rollback : {$runScriptsByTag['rollback']}";
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'Version Update',
                'eventId'   => "Tag '{$tagVersion}'",
                'level'     => 'INFO',
                'eventType' => $info
            ]);
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'Version Update Controller',
            'eventId'   => "Update",
            'level'     => 'INFO',
            'eventType' => "End of the update"
        ]);

        return true;
    }

    /**
     * Main function to run sql files
     * @param string $sqlFilePath
     * @param string $docserverMigrationFolderPath
     * @return  bool    Return true if postgresql dump and sql file executed with success
     * or return false if postgresql dump failed
     * @throws Exception
     */
    public static function executeTagSqlFile(string $sqlFilePath, string $docserverMigrationFolderPath): bool
    {
        if (empty($sqlFilePath)) {
            throw new Exception('$sqlFilePath must be a non empty string');
        }
        if (empty($docserverMigrationFolderPath)) {
            throw new Exception('$docserverMigrationFolderPath must be a non empty string');
        }

        if (file_exists($sqlFilePath)) {
            $config = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
            $actualTime = date("dmY-His");
            $tablesToSave = '';

            $fileContent = file_get_contents($sqlFilePath);
            $explodedFile = explode("\n", $fileContent);

            foreach ($explodedFile as $key => $line) {
                if (strpos($line, '--DATABASE_BACKUP') !== false) {
                    $lineNb = $key;
                }
            }

            if (isset($lineNb)) {
                $explodedLine = explode('|', $explodedFile[$lineNb]);
                array_shift($explodedLine);

                foreach ($explodedLine as $table) {
                    if (!empty($table)) {
                        $tablesToSave .= ' -t ' . trim($table);
                    }
                }
            }

            $backupFile = $docserverMigrationFolderPath . "/backupDB_maarchcourrier_$actualTime.sql";
            $dbname = "postgresql://{$config['database'][0]['user']}:{$config['database'][0]['password']}@" .
                "{$config['database'][0]['server']}:{$config['database'][0]['port']}/{$config['database'][0]['name']}";
            exec(
                "pg_dump --dbname=\"$dbname\" $tablesToSave -a > \"$backupFile\"",
                $output,
                $intReturn
            );

            if ($intReturn != 0) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'Version Update',
                    'level'     => 'CRITICAL',
                    'eventType' => '[executeTagSqlFile] : Postgresql dump failed :' .
                        ' One or more backup tables does not exist OR the backup path is not reachable',
                    'eventId'   => 'Execute Update'
                ]);
                return false;
            }

            DatabaseModel::exec($fileContent);
            $fileName = explode('/', $sqlFilePath)[1];

            HistoryController::add([
                'tableName' => 'none',
                'recordId'  => $fileName,
                'eventType' => 'UP',
                'userId'    => $GLOBALS['id'],
                'info'      => _DB_UPDATED_WITH_FILE . ' : ' . $fileName,
                'moduleId'  => null,
                'eventId'   => 'databaseUpdate',
            ]);
        }

        return true;
    }

    /**
     * Main function to run php files
     * @param string[] $folderFiles
     * @param string $tagVersion
     * @return  int[]       Array of numbers with 'numberOfFiles', 'success', 'errors' and 'rollback'
     * @throws Exception
     */
    public static function runScriptsByTag(array $folderFiles, string $tagVersion): array
    {
        if (empty($folderFiles)) {
            throw new Exception('$folderFiles must be a non empty array');
        }
        if (empty($tagVersion)) {
            throw new Exception('$tagVersion must be a non empty string');
        }

        $numberOfFiles = 0;
        $success = 0;
        $errors = 0;
        $rollback = 0;

        foreach ($folderFiles as $fileName) {
            if (in_array($fileName, ['.', '..'])) {
                continue;
            }

            $numberOfFiles++;
            $filePath = "migration/$tagVersion/$fileName";
            $migrationClass = require $filePath;
            $migration = new $migrationClass();

            if (empty($migration) || !$migration instanceof AutoUpdateInterface) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'Version Update',
                    'eventId'   => 'Run Scripts By Tag',
                    'level'     => 'CRITICAL',
                    'eventType' => "Could not find 'AutoUpdateInterface' of an anonymous class from '$filePath'"
                ]);
                $errors++;
                continue;
            }

            try {
                $migration->backup();
            } catch (Throwable $th) {
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'Version Update',
                    'eventId'   => 'Run Scripts By Tag',
                    'level'     => 'CRITICAL',
                    'eventType' => "Throwable - BACKUP : " . $th->getMessage()
                ]);
                $errors++;
                continue;
            }

            try {
                $migration->update();

                $success++;
            } catch (Throwable $th) {
                $logInfo = "Throwable - UPDATE : " . $th->getMessage();
                $errors++;

                try {
                    $migration->rollback();
                    $rollback++;
                } catch (Throwable $th) {
                    $logInfo .= " | Throwable - ROLLBACK : " . $th->getMessage();
                }

                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'Version Update',
                    'eventId'   => 'Run Scripts By Tag',
                    'level'     => 'CRITICAL',
                    'eventType' => $logInfo
                ]);
                continue;
            }
        }

        return ['numberOfFiles' => $numberOfFiles, 'success' => $success, 'errors' => $errors, 'rollback' => $rollback];
    }
}
