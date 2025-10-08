<?php

/**
 * Copyright Maarch since 2008 under license.
 * See LICENSE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Index Contacts Script
 * @author dev@maarch.org
 */

namespace Mercure\scripts;

// phpcs:ignore
require 'vendor/autoload.php';

use Contact\models\ContactModel;
use Convert\controllers\FullTextController;
use Exception;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabasePDO;
use Zend_Search_Lucene;
use Zend_Search_Lucene_Analysis_Analyzer;
use Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive;
use Zend_Search_Lucene_Document;
use Zend_Search_Lucene_Field;
use Zend_Search_Lucene_Index_Term;

// SAMPLE COMMANDS :
// (in root app)
// Launch indexation contacts :
// php src/app/mercure/scripts/IndexContactsScript.php --customId yourcustom --fileConfig 'config/ladConfiguration.json'

// ARGS
// --customId   : Instance id;
// --fileConfig : Path of LAD file configuration (optionnal);
// --reindexAll : Re-index all contacts database (optionnal);

// phpcs:ignore
try {
    IndexContactsScript::initialize($argv);
} catch (Exception $e) {
    echo "[Exception] {$e->getMessage()}";
}

class IndexContactsScript
{
    /**
     * @param array $args
     *
     * @return void
     * @throws Exception
     */
    public static function initialize(array $args): void
    {
        $customId = '';
        $fileConfiguration = '';
        $reindexAll = false;

        if (array_search('--customId', $args) > 0) {
            $cmd = array_search('--customId', $args);
            $customId = $args[$cmd + 1];

            $fileConfiguration = 'custom/' . $customId . '/config/ladConfiguration.json';
        }

        if (array_search('--fileConfig', $args) > 0) {
            $cmd = array_search('--fileConfig', $args);
            $fileConfiguration = $args[$cmd + 1];
        }

        if (array_search('--reindexAll', $args) > 0) {
            $reindexAll = true;
        }

        IndexContactsScript::generateIndex([
            'customId'   => $customId,
            'fileConfig' => $fileConfiguration,
            'indexAll'   => $reindexAll
        ]);
    }

    /**
     * @param array $args
     *
     * @return bool
     * @throws Exception
     */
    public static function generateIndex(array $args): bool
    {
        DatabasePDO::reset();
        new DatabasePDO(['customId' => $args['customId']]);

        $fileConfig = (!empty($args['fileConfig']) && is_file($args['fileConfig']))
            ? $args['fileConfig']
            : 'custom/' . $args['customId'] . '/config/ladConfiguration.json';

        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => $fileConfig]);
        if (empty($ladConfiguration)) {
            echo "/!\\ LAD configuration file does not exist \n";
            return false;
        }

        $baseDirectory = $ladConfiguration['config']['mercureLadDirectory'] ?? null;
        if (!$baseDirectory) {
            echo "/!\\ Base directory configuration is missing or empty.\n";
            return false;
        }

        $contactsIndexesDirectory = $baseDirectory . "/Lexiques/ContactsIdx";
        $contactsLexiconsDirectory = $baseDirectory . "/Lexiques/ContactsLexiques";

        // Check if the directories exist or not
        if (!is_dir($contactsIndexesDirectory)) {
            echo "/!\\ Contacts Indexes Directory does not exist: $contactsIndexesDirectory\n";
            return false;
        }

        if (!is_dir($contactsLexiconsDirectory)) {
            echo "/!\\ Contacts Lexicons Directory does not exist: $contactsLexiconsDirectory\n";
            return false;
        }

        //Création des dossiers Lexiques et Index si non existants
        $contactsLexiconsDirectory .= DIRECTORY_SEPARATOR . $args['customId'];
        if (!is_dir($contactsLexiconsDirectory)) {
            mkdir($contactsLexiconsDirectory, 0775, true);
        }

        $contactsIndexesDirectory .= DIRECTORY_SEPARATOR . $args['customId'];
        if (!is_dir($contactsIndexesDirectory)) {
            mkdir($contactsIndexesDirectory, 0775, true);
        }

        //Ouverture des index Lucene
        try {
            if (FullTextController::isDirEmpty($contactsIndexesDirectory)) {
                $index = Zend_Search_Lucene::create($contactsIndexesDirectory);
            } else {
                $index = Zend_Search_Lucene::open($contactsIndexesDirectory);
            }

            $index->setFormatVersion(Zend_Search_Lucene::FORMAT_2_3);
            Zend_Search_Lucene_Analysis_Analyzer::setDefault(
                new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive()
            );
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        //Construction de la configuration
        $tabLexicon = $tabSelect = [];
        foreach ($ladConfiguration['contactsIndexation'] as $fieldIndexation) {
            $tabSelect[] = $fieldIndexation['database'];

            if (!is_null($fieldIndexation['lexicon'])) {
                $tabLexicon[$fieldIndexation['lexicon']] = [];

                //Initialiser le lexique si le fichier Lexique existe déjà
                $filePath = $contactsLexiconsDirectory . DIRECTORY_SEPARATOR . $fieldIndexation['lexicon'] . ".txt";

                if (!$args['indexAll'] && is_file($filePath)) {
                    $lexique = fopen($filePath, "r");
                    while (($entreeLexique = fgets($lexique)) !== false) {
                        if (!empty($entreeLexique)) {
                            $tabLexicon[$fieldIndexation['lexicon']][] = trim($entreeLexique);
                        }
                    }

                    fclose($lexique);
                }
            }
        }

        //Récupération des contacts
        $contactsToIndexes = ContactModel::get([
            'select'  => $tabSelect,
            'orderBy' => ['id'],
            'where'   => (!$args['indexAll']) ? ['lad_indexation is false'] : []
        ]);

        $listIdToUpdate = [];
        echo "[" . date("Y-m-d H:i:s") . "] Début de l'indexation \n";

        echo "0/0";

        foreach ($contactsToIndexes as $key1 => $c) {
            echo "\e[2K"; # clear whole line
            echo "\e[1G"; # move cursor to column 1
            echo "Indexation contact " . ($key1 + 1) . "/" . count($contactsToIndexes);

            //Suppression de l'ID en cours
            $term = new Zend_Search_Lucene_Index_Term((int)$c['id'], 'Idx');
            $terms = $index->termDocs($term);
            foreach ($terms as $value) {
                $index->delete($value);
            }

            $cIdx = new Zend_Search_Lucene_Document();

            foreach ($ladConfiguration['contactsIndexation'] as $key2 => $fieldIndexation) {
                try {
                    if ($key2 == "id") {
                        $cIdx->addField(
                            Zend_Search_Lucene_Field::UnIndexed($fieldIndexation['lucene'], (string)$c['id'])
                        );
                    } else {
                        $cIdx->addField(
                            Zend_Search_Lucene_Field::text($fieldIndexation['lucene'], $c[$key2], 'utf-8')
                        );
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                    return false;
                }

                //Ajout des informations aux lexiques
                if (isset($tabLexicon[$fieldIndexation['lexicon']])) {
                    if (!in_array($c[$key2], $tabLexicon[$fieldIndexation['lexicon']]) && !empty($c[$key2])) {
                        $tabLexicon[$fieldIndexation['lexicon']][] = $c[$key2];
                    }
                }
            }

            $cIdx->addField(Zend_Search_Lucene_Field::text('UserMWS', $args['customId'], 'utf-8'));

            $index->addDocument($cIdx);
            $index->commit();

            $listIdToUpdate[] = $c['id'];

            if ((int)$c['id'] % 1000 === 0) {
                echo " (optimisation ...)";
                $index->optimize();

                ContactModel::update([
                    'set'   => ['lad_indexation' => 'true'],
                    'where' => ['id in (?)'],
                    'data'  => [$listIdToUpdate]
                ]);

                $listIdToUpdate = [];
            }
        }

        if (count($contactsToIndexes) > 0) {
            // Optimisation finale
            echo " (optimisation ...)\n";
            $index->optimize();
            echo "[" . date("Y-m-d H:i:s") . "] Fin de l'indexation \n";

            if (count($listIdToUpdate) > 0) {
                ContactModel::update([
                    'set'   => ['lad_indexation' => 1],
                    'where' => ['id in (?)'],
                    'data'  => [$listIdToUpdate]
                ]);
            }

            echo "[" . date("Y-m-d H:i:s") . "] Ecriture des lexiques \n";
            foreach ($tabLexicon as $keyLexicon => $l) {
                //sort($l);
                $filePath = $contactsLexiconsDirectory . DIRECTORY_SEPARATOR . $keyLexicon . ".txt";
                $lexiconFile = fopen($filePath, "w");
                if ($lexiconFile === false) {
                    echo "Erreur dans la génération du fichier de lexique : $filePath";
                    return false;
                }

                foreach ($l as $entry) {
                    fwrite($lexiconFile, $entry . "\n");
                }
                fclose($lexiconFile);
            }

            $filePath = $contactsLexiconsDirectory . DIRECTORY_SEPARATOR . "lastindexation.flag";
            $flagFile = fopen($filePath, "w");
            if (!$flagFile) {
                echo "Erreur d'écriture du fichier $filePath !\n";
            } else {
                fwrite($flagFile, date("d-m-Y H:i:s"));
                fclose($flagFile);
            }
        } else {
            echo "\n";
        }
        echo "[" . date("Y-m-d H:i:s") . "] Script d'indexation terminé !\n";
        return true;
    }
}
