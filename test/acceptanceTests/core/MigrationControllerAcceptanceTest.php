<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   Acceptance Test For Migration Folder
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace MaarchCourrier\AcceptanceTests\core;

use SrcCore\models\CoreConfigModel;
use MaarchCourrier\Tests\CourrierTestCase;
use SrcCore\interfaces\AutoUpdateInterface;

class MigrationControllerAcceptanceTest extends CourrierTestCase
{
    /**
     * Look for any php files in the migration folder recursively and check the class name.
     * 
     * @param   string      $directoryToScan    (Optional) if path is different than 'maarchDirectory/migration'
     * @return  string[]    Error messages
     */
    private static function doesMigrationFolderHasDuplicateClasses(string $directoryToScan = ''): array
    {
        $output     = [];
        $classNames = [];

        if (empty($directoryToScan)) {
            $configJson = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
            $directoryToScan = $configJson['config']['maarchDirectory'];
        }

        $iterator   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directoryToScan), \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $tokens = token_get_all(file_get_contents($file->getPathname()));

                foreach ($tokens as $key => $token) {
                    if (is_array($token)) {
                        $className = '';
                        $twoTokenAway = gettype($tokens[$key + 2] ?? []) === 'array' ? $tokens[$key + 2] ?? [] : [];

                        if ($token[0] === T_CLASS && $twoTokenAway[0] === T_STRING && !empty($twoTokenAway[1])) {
                            $className = trim($twoTokenAway[1]);

                            if (isset($classNames[$className])) {
                                $output[] = "Duplicate class name detected '$className' in " . $file->getPathname();
                            }

                            $classNames[$className] = $file->getPathname();
                        }
                    }
                }
            }
        }

        unset($iterator);

        return $output;
    }

    public function testClassNameDuplicatesInMigrationFolderExpectNoClassDuplicates(): void
    {
        // Arrange
        $directoryToScan = getcwd() . '/migration';

        // Act
        $migrationClassDuplicateList = MigrationControllerAcceptanceTest::doesMigrationFolderHasDuplicateClasses($directoryToScan);

        // Assert
        $this->assertEmpty($migrationClassDuplicateList, implode(", ", $migrationClassDuplicateList ?? []));
    }

    /**
     * Look for any php files in the migration folder recursively and check the interface name in the class.
     * 
     * @param   string      $directoryToScan    (Optional) if path is different than 'maarchDirectory/migration'
     * @return  string[]    Error messages
     */
    private static function doesMigrationScriptsImplementAutoUpdateInterfaceInMigrationFolder(string $directoryToScan = ''): array
    {
        if (empty($directoryToScan)) {
            $configJson = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
            $directoryToScan = $configJson['config']['maarchDirectory'];
        }

        $output     = [];
        $iterator   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directoryToScan), \RecursiveIteratorIterator::SELF_FIRST);

        foreach (iterator_to_array($iterator) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {

                $classObj = null;
                try {
                    $class = require $file->getPathname();
                    $classObj = new $class();
                } catch (\Throwable $th) {
                    $output[] = $th->getMessage();
                    continue;
                }

                if (!in_array(AutoUpdateInterface::class, class_implements($classObj))) {
                    $output[] = "The class " . get_class($classObj) . " does not implement AutoUpdateInterface in '{$file->getPathname()}'";
                }
            }
        }

        return $output;
    }

    public function testClassFilesHasAutoUpdateInterfaceInMigrationFolderExpectAllClassesToImplementIt(): void
    {
        // Arrange
        $directoryToScan = getcwd() . '/migration';

        // Act
        $migrationClassDuplicateList = MigrationControllerAcceptanceTest::doesMigrationScriptsImplementAutoUpdateInterfaceInMigrationFolder($directoryToScan);

        // Assert
        $this->assertEmpty($migrationClassDuplicateList, implode(", ", $migrationClassDuplicateList ?? []));
    }
}
