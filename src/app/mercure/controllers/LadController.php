<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief LadController
 * @author dev@maarch.org
 */

namespace Mercure\controllers;

use Configuration\models\ConfigurationModel;
use Contact\models\ContactModel;
use DateTime;
use Exception;
use Group\controllers\PrivilegeController;
use setasign\Fpdi\Tcpdf\Fpdi;
use SrcCore\controllers\LogsController;
use Respect\Validation\Validator;
use Slim\Psr7\Request;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

class LadController
{
    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function isEnabled(Request $request, Response $response): Response
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            return $response->withJson(['enabled' => false]);
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['enabledLad'])) {
            return $response->withJson(['enabled' => false]);
        }

        return $response->withJson(['enabled' => true]);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public function ladRequest(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!Validator::notEmpty()->validate($body['encodedResource'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body encodedResource is empty']);
        }
        if (!Validator::notEmpty()->validate($body['extension'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body extension is empty']);
        }

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            return $response->withStatus(400)->withJson(['errors' => 'Mercure configuration is not enabled']);
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['enabledLad'])) {
            return $response->withStatus(200)->withJson(['message' => 'Mercure LAD is not enabled']);
        }

        $ladResult = [];
        if ($configuration['mwsLadPriority']) {
            if (!Validator::notEmpty()->validate($body['filename'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Body filename is empty']);
            }

            $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);
            if (empty($ladConfiguration)) {
                return $response->withStatus(400)->withJson([
                    'errors' => 'LAD configuration file does not exist'
                ]);
            }

            $ladResult = MwsController::launchLadMws([
                'encodedResource' => $body['encodedResource'],
                'filename'        => $body['filename']
            ]);
        } else {
            $ladResult = LadController::launchLad([
                'encodedResource' => $body['encodedResource'],
                'extension'       => $body['extension']
            ]);
        }

        if (!empty($ladResult['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $ladResult['errors']]);
        }

        return $response->withJson($ladResult);
    }

    /**
     * @return string
     */
    private static function generateTestPdf(): string
    {
        $libPath = CoreConfigModel::getFpdiPdfParserLibrary();
        if (file_exists($libPath)) {
            require_once($libPath);
        }
        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);
        $pdf->AddPage();

        $pdf->SetFont('', 'B', 14);
        $pdf->Write(5, 'Objet : Courrier test');

        $tmpDir = CoreConfigModel::getTmpPath();
        $filePathOnTmp = $tmpDir . 'fileTestLad.pdf';
        $pdf->Output($filePathOnTmp, 'F');

        return $filePathOnTmp;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public function testLad(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_mercure', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_mercure']);
        if (empty($configuration)) {
            $data = json_encode([
                'enabledLad'     => true,
                'mwsLadPriority' => false,
                'mws'            => [
                    'url'            => "",
                    'login'          => "",
                    'password'       => "",
                    'tokenMws'       => "",
                    'loginMaarch'    => "",
                    'passwordMaarch' => "",
                ]
            ]);
            ConfigurationModel::create(['value' => $data, 'privilege' => 'admin_mercure']);
        }

        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);
        if (empty($ladConfiguration)) {
            return $response->withStatus(400)->withJson(['errors' => 'LAD configuration file does not exist']);
        }

        $mercureLadDirectory = $ladConfiguration['config']['mercureLadDirectory'];

        if (!is_dir($mercureLadDirectory)) {
            return $response->withStatus(400)->withJson(['errors' => 'Mercure module directory does not exist']);
        }

        if (
            !is_file($mercureLadDirectory . DIRECTORY_SEPARATOR . 'Mercure5') ||
            !is_executable($mercureLadDirectory . DIRECTORY_SEPARATOR . 'Mercure5')
        ) {
            return $response->withStatus(400)->withJson([
                'errors' => 'Mercure5 exe is not present in the distribution or is not executable'
            ]);
        }

        if (
            !is_file($mercureLadDirectory . DIRECTORY_SEPARATOR . 'ugrep') ||
            !is_executable($mercureLadDirectory . DIRECTORY_SEPARATOR . 'ugrep')
        ) {
            return $response->withStatus(400)->withJson([
                'errors' => 'ugrep exe is not present in the distribution or is not executable'
            ]);
        }

        $testFile = LadController::generateTestPdf();
        $encodedResource = base64_encode(file_get_contents($testFile));

        $ladResult = LadController::launchLad([
            'encodedResource' => $encodedResource,
            'extension'       => 'pdf'
        ]);

        if (!empty($ladResult['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $ladResult['errors']]);
        }

        return $response->withStatus(204);
    }

    /**
     * @param array $aArgs
     *
     * @return array
     * @throws Exception
     */
    public static function launchLad(array $aArgs): array
    {
        ValidatorModel::notEmpty($aArgs, ['encodedResource', 'extension']);
        ValidatorModel::stringType($aArgs, ['encodedResource', 'extension']);

        $customId = CoreConfigModel::getCustomId();

        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);
        if (empty($ladConfiguration)) {
            return ['errors' => 'LAD configuration file does not exist'];
        }

        $tmpPath = $ladConfiguration['config']['mercureLadDirectory'] . '/IN/' . $customId . DIRECTORY_SEPARATOR;
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0777);
            mkdir(
                $ladConfiguration['config']['mercureLadDirectory'] . '/OUT/' . $customId . DIRECTORY_SEPARATOR,
                0777
            );
        }

        $tmpFilename = 'lad' . rand() . '_' . rand();

        $writeFileResult = file_put_contents(
            $tmpPath . $tmpFilename . '.' . $aArgs['extension'],
            base64_decode($aArgs['encodedResource'])
        );
        if (!$writeFileResult) {
            return ['errors' => 'Document writing error in input directory'];
        }

        //Mercure5 fileIn fileOut fileParams
        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'mercure',
            'level'     => 'INFO',
            'tableName' => '',
            'recordId'  => '',
            'eventType' => "LAD task",
            'eventId'   => "Launch LAD on file {$tmpPath}{$tmpFilename}.{$aArgs['extension']}"
        ]);

        $outXmlFilename = $ladConfiguration['config']['mercureLadDirectory'] . '/OUT/' .
            $customId . DIRECTORY_SEPARATOR . $tmpFilename . '.xml';

        $command = $ladConfiguration['config']['mercureLadDirectory'] . '/Mercure5 '
            . $tmpPath . $tmpFilename . '.' . $aArgs['extension'] . ' '
            . $outXmlFilename . ' '
            . $ladConfiguration['config']['mercureLadDirectory'] . '/MERCURE5_I1_LAD_COURRIER_v5.cfg';

        exec($command . ' 2>&1', $output, $return);

        if ($return == 0) {
            $mappingMercure = $ladConfiguration['mappingLadFields'];

            $outputXml = CoreConfigModel::getXmlLoaded(['path' => $outXmlFilename]);
            $mandatoryFields = [
                'subject',
                'documentDate',
                'contactIdx'
            ];
            $aReturn = [];

            foreach ($mandatoryFields as $f) {
                $aReturn[$f] = "";
            }
            if ($outputXml) {
                foreach ($outputXml->field as $field) {
                    $nameAttributeKey = 'n';
                    $nameAttribute = (string)$field->attributes()->$nameAttributeKey;
                    $disabledField = false;
                    $normalizationRule = '';
                    $normalizationFormat = null;

                    if (isset($mappingMercure[$nameAttribute])) {
                        if (isset($mappingMercure[$nameAttribute]['disabled'])) {
                            $disabledField = $mappingMercure[$nameAttribute]['disabled'];
                        }
                        if (isset($mappingMercure[$nameAttribute]['normalizationRule'])) {
                            $normalizationRule = $mappingMercure[$nameAttribute]['normalizationRule'];
                        }
                        if (isset($mappingMercure[$nameAttribute]['normalizationFormat'])) {
                            $normalizationFormat = $mappingMercure[$nameAttribute]['normalizationFormat'];
                        }
                        if (isset($mappingMercure[$nameAttribute]['key'])) {
                            $nameAttribute = $mappingMercure[$nameAttribute]['key'];
                        }
                    }

                    if (
                        !$disabledField &&
                        (!array_key_exists($nameAttribute, $aReturn) || empty($aReturn[$nameAttribute]))
                    ) {
                        $aReturn[$nameAttribute] = LadController::normalizeField(
                            (string)$field[0],
                            $normalizationRule,
                            $normalizationFormat
                        );
                    }
                }

                foreach ($outputXml->SenderContact as $contact) {
                    $aReturn["contactIdx"] = (string)$contact->Idx[0];
                }
            } else {
                return ['errors' => 'Output XML  LAD file doesn\'t exists'];
            }

            if (is_file($tmpPath . $tmpFilename . '.' . $aArgs['extension'])) {
                unlink($tmpPath . $tmpFilename . '.' . $aArgs['extension']);
            }

            //Suppression du fichier xml
            unlink($outXmlFilename);
        } else {
            if (is_file($outXmlFilename)) {
                $outputXml = CoreConfigModel::getXmlLoaded(['path' => $outXmlFilename]);
                foreach ($outputXml->status as $status) {
                    if (str_contains(strtolower($status), 'quota exceeded')) {
                        return ['errors' => 'Number of LAD request exceeded, please contact Maarch'];
                    }
                }
            }
            $tabErrors = [];

            $tagsErrToCheck = [
                'not found',
                'error',
                'permission denied',
                'sh: 1'
            ];

            foreach ($output as $numLine => $lineOutput) {
                if (LadController::contains($lineOutput, $tagsErrToCheck)) {
                    $tabErrors[] = "[" . $numLine . "]" . $lineOutput;
                }
            }

            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'mercure',
                'level'     => 'ERROR',
                'tableName' => '',
                'recordId'  => '',
                'eventType' => "LAD task",
                'eventId'   => "LAD task error on file {$tmpPath}{$tmpFilename} . {$aArgs['extension']}," .
                    " return : {$return}, errors : " . implode(",", $tabErrors)
            ]);
            $aReturn = ['errors' => $tabErrors, 'output' => $output, 'return' => $return, 'cmd' => $command];

            return $aReturn;
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'mercure',
            'level'     => 'INFO',
            'tableName' => '',
            'recordId'  => '',
            'eventType' => "LAD task",
            'eventId'   => "LAD task success on file {$tmpPath}{$tmpFilename} . {$aArgs['extension']}"
        ]);

        return $aReturn;
    }

    /**
     * @param $strToCheck
     * @param array $arrTags
     *
     * @return bool
     */
    private static function contains($strToCheck, array $arrTags): bool
    {
        foreach ($arrTags as $tag) {
            if (stripos($strToCheck, $tag) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $fieldContent
     * @param $normalizationRule
     * @param $normalizationFormat
     * @return string
     * @throws Exception
     */
    private static function normalizeField($fieldContent, $normalizationRule, $normalizationFormat = null): string
    {
        switch ($normalizationRule) {
            case 'DATE':
                $result = LadController::normalizeDate($fieldContent, $normalizationFormat);
                break;
            default:
                $result = $fieldContent;
                break;
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws Exception
     */
    public static function getContactsIndexationState(Request $request, Response $response): Response
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_mercure', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $ladConfiguration = CoreConfigModel::getJsonLoaded(['path' => 'config/ladConfiguration.json']);

        if (empty($ladConfiguration)) {
            return $response->withJson(['errors' => 'LAD configuration file does not exist']);
        }

        $customId = CoreConfigModel::getCustomId();
        $indexedContacts = ContactModel::get([
            'select' => ['COUNT(*)'],
            'where'  => ['lad_indexation = ? '],
            'data'   => [true]
        ]);
        $countIndexedContacts = (int)$indexedContacts[0]['count'];

        $allContacts = ContactModel::get([
            'select' => ['COUNT(*)']
        ]);
        $countAllContacts = (int)$allContacts[0]['count'];

        $lexDirectory = $ladConfiguration['config']['mercureLadDirectory'] .
            "/Lexiques/ContactsLexiques" . DIRECTORY_SEPARATOR . $customId;
        if (is_file($lexDirectory . DIRECTORY_SEPARATOR . "lastindexation.flag")) {
            $flagFile = fopen($lexDirectory . DIRECTORY_SEPARATOR . "lastindexation.flag", "r");
            if ($flagFile === false) {
                $dateIndexation = "";
            } else {
                $dateIndexation = fgets($flagFile);
                fclose($flagFile);
            }
        } else {
            $dateIndexation = "";
        }

        return $response->withJson([
            'dateIndexation'        => $dateIndexation,
            'countIndexedContacts'  => $countIndexedContacts,
            'countAllContacts'      => $countAllContacts,
            'pctIndexationContacts' => ($countIndexedContacts * 100) / $countAllContacts,
        ]);
    }

    /**
     * @param string $content
     * @param string $format
     *
     * @return string
     * @throws Exception
     */
    private static function normalizeDate(string $content, string $format): string
    {
        $result = strtolower($content);
        $result = str_replace(" ", "", $result);
        $result = LadController::stripAccents($result);
        $result = LadController::replaceMonth($result);

        $result = LadController::getElementsDate($result);
        if (!$result) {
            return "";
        }

        $date = new DateTime($result['year'] . "-" . $result['month'] . "-" . $result['day']);

        return $date->format($format);
    }

    /**
     * @param string $dateString
     *
     * @return array|false
     */
    private static function getElementsDate(string $dateString): array|false
    {
        $strPattern = "/([0-9]|01|02|03|04|05|06|07|08|09|10|11|12|13|14|15|16|17|18|19|20|" .
            "21|22|23|24|25|26|27|28|29|30|31|premier|un|deux|trois|quatre|cinq|" .
            "six|sept|huit|neuf|dix|onze)\s?\.?\\?\/?-?_?(12|11|10|09|08|07|06|" .
            "05|04|03|02|01|décembre|decembre|novembre|octobre|septembre|aout|" .
            "août|juillet|juin|mai|avril|mars|fevrier|février|janvier)\s?\.?\\?\/?-?_?" .
            "(20[0-9][0-9])/m";
        preg_match_all($strPattern, $dateString, $matches, PREG_SET_ORDER, 0);

        if (!empty($matches[0]) && isset($matches[0][1], $matches[0][2], $matches[0][3])) {
            return [
                'day'   => $matches[0][1],
                'month' => $matches[0][2],
                'year'  => $matches[0][3],
            ];
        }
        return false;
    }

    /**
     * Remove accents from a string.
     *
     * @param string $content The input string from which to strip accents.
     *
     * @return string The string with accents removed.
     */
    private static function stripAccents(string $content): string
    {
        $search = [
            'À',
            'Á',
            'Â',
            'Ã',
            'Ä',
            'Å',
            'Ç',
            'È',
            'É',
            'Ê',
            'Ë',
            'Ì',
            'Í',
            'Î',
            'Ï',
            'Ò',
            'Ó',
            'Ô',
            'Õ',
            'Ö',
            'Ù',
            'Ú',
            'Û',
            'Ü',
            'Ý',
            'à',
            'á',
            'â',
            'ã',
            'ä',
            'å',
            'ç',
            'è',
            'é',
            'ê',
            'ë',
            'ì',
            'í',
            'î',
            'ï',
            'ð',
            'ò',
            'ó',
            'ô',
            'õ',
            'ö',
            'ù',
            'ú',
            'û',
            'ü',
            'ý',
            'ÿ'
        ];
        $replace = [
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'C',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'Y',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'c',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y'
        ];

        return str_replace($search, $replace, $content);
    }

    /**
     * @param string $dateString
     * @return string
     */
    private static function replaceMonth(string $dateString): string
    {
        $search = [
            'janvier',
            'janv',
            'fevrier',
            'fev',
            'mars',
            'mar',
            'avril',
            'avr',
            'mai',
            'juin',
            'juillet',
            'juil',
            'aout',
            'aou',
            'septembre',
            'sept',
            'octobre',
            'oct',
            'novembre',
            'nov',
            'decembre',
            'dec'
        ];
        $replace = [
            '01',
            '01',
            '02',
            '02',
            '03',
            '03',
            '04',
            '04',
            '05',
            '06',
            '07',
            '07',
            '08',
            '08',
            '09',
            '09',
            '10',
            '10',
            '11',
            '11',
            '12',
            '12'
        ];

        return str_replace($search, $replace, $dateString);
    }
}
