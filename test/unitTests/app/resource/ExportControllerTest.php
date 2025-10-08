<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\resource;

use Exception;
use Folder\models\ResourceFolderModel;
use Group\models\GroupModel;
use Group\models\PrivilegeModel;
use MaarchCourrier\Tests\CourrierTestCase;
use Resource\controllers\ExportController;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use SrcCore\http\Response;
use User\models\UserModel;

class ExportControllerTest extends CourrierTestCase
{
    private static array $resourcesToRemove = [];
    private static array $group = [];
    private static string $privilegeId = 'include_folders_and_followed_resources_perimeter';

    protected function tearDown(): void
    {
        foreach (self::$resourcesToRemove as $resId) {
            ResModel::delete([
                'where' => ['res_id = ?'],
                'data'  => [$resId]
            ]);
            ResourceFolderModel::delete(['where' => ['id in (?)'], 'data' => [$resId]]);

        }
        if (!empty(self::$group)) {
            PrivilegeModel::addPrivilegeToGroup(['privilegeId' => self::$privilegeId, 'groupId' => self::$group['group_id']]);
            self::$group = [];
        }
    }

    public function testGetExportTemplates(): void
    {
        $exportController = new ExportController();

        //  GET
        $request = $this->createRequest('GET');

        $response = $exportController->getExportTemplates($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->templates);
        $this->assertNotEmpty($responseBody->templates->pdf);
        $this->assertNotEmpty($responseBody->templates->csv);
    }

    /**
     * @throws Exception
     */
    public function testUpdateExport(): void
    {
        $GLOBALS['login'] = 'bbain';
        $userInfo = UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $ExportController = new ExportController();

        //  PUT
        $args = [
            "resources" => $GLOBALS['resources'],
            "delimiter" => ';',
            "format"    => 'pdf',
            "data"      => [
                [
                    "value"      => "subject",
                    "label"      => "Sujet",
                    "isFunction" => false
                ],
                [
                    "value"      => "getStatus",
                    "label"      => "Status",
                    "isFunction" => true
                ],
                [
                    "value"      => "getPriority",
                    "label"      => "Priorité",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDetailLink",
                    "label"      => "Lien page détaillée",
                    "isFunction" => true
                ],
                [
                    "value"      => "getInitiatorEntity",
                    "label"      => "Entité initiatrice",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntity",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntityType",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getCategory",
                    "label"      => "Catégorie",
                    "isFunction" => true
                ],
                [
                    "value"      => "getCopies",
                    "label"      => "Utilisateurs en copie",
                    "isFunction" => true
                ],
                [
                    "value"      => "getSenders",
                    "label"      => "Expéditeurs",
                    "isFunction" => true
                ],
                [
                    "value"      => "getRecipients",
                    "label"      => "Destinataires",
                    "isFunction" => true
                ],
                [
                    "value"      => "getTypist",
                    "label"      => "Créateurs",
                    "isFunction" => true
                ],
                [
                    "value"      => "getAssignee",
                    "label"      => "Attributaire",
                    "isFunction" => true
                ],
                [
                    "value"      => "getTags",
                    "label"      => "Mots-clés",
                    "isFunction" => true
                ],
                [
                    "value"      => "getSignatories",
                    "label"      => "Signataires",
                    "isFunction" => true
                ],
                [
                    "value"      => "getSignatureDates",
                    "label"      => "Date de signature",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDepartment",
                    "label"      => "Département de l'expéditeur",
                    "isFunction" => true
                ],
                [
                    "value"      => "getAcknowledgementSendDate",
                    "label"      => "Date d'accusé de réception",
                    "isFunction" => true
                ],
                [
                    "value"      => "getParentFolder",
                    "label"      => "Dossiers parent",
                    "isFunction" => true
                ],
                [
                    "value"      => "getFolder",
                    "label"      => "Dossiers",
                    "isFunction" => true
                ],
                [
                    "value"      => "doc_date",
                    "label"      => "Date du courrier",
                    "isFunction" => false
                ],
                [
                    "value"      => "custom_4",
                    "label"      => "Champ personnalisé",
                    "isFunction" => true
                ],
            ]
        ];

        //PDF
        $fullRequest = $this->createRequestWithBody('PUT', $args);

        $response = $ExportController->updateExport($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame(null, $responseBody);
        $headers = $response->getHeaders();
        $this->assertSame('application/pdf', $headers['Content-Type'][0]);

        $response = $ExportController->updateExport($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame(null, $responseBody);
        $headers = $response->getHeaders();
        $this->assertSame('application/pdf', $headers['Content-Type'][0]);

        //  GET
        $request = $this->createRequest('GET');

        $response = $ExportController->getExportTemplates($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $templateData = (array)$responseBody->templates->pdf->data;
        foreach ($templateData as $key => $value) {
            $templateData[$key] = (array)$value;
        }
        $this->assertSame($args['data'], $templateData);

        //CSV
        $args['format'] = 'csv';
        $fullRequest = $this->createRequestWithBody('PUT', $args);

        $response = $ExportController->updateExport($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        //  GET
        $request = $this->createRequest('GET');

        $response = $ExportController->getExportTemplates($request, new Response());
        $responseBody = json_decode((string)$response->getBody());

        $templateData = (array)$responseBody->templates->csv->data;
        foreach ($templateData as $key => $value) {
            $templateData[$key] = (array)$value;
        }
        $this->assertSame($args['data'], $templateData);
        $this->assertSame(';', $responseBody->templates->csv->delimiter);


        //ERRORS
        unset($args['data'][2]['label']);
        $fullRequest = $this->createRequestWithBody('PUT', $args);
        $response = $ExportController->updateExport($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('One data is not set well', $responseBody->errors);

        unset($args['data']);
        $fullRequest = $this->createRequestWithBody('PUT', $args);
        $response = $ExportController->updateExport($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Data data is empty or not an array', $responseBody->errors);

        $args['delimiter'] = 't';
        $fullRequest = $this->createRequestWithBody('PUT', $args);
        $response = $ExportController->updateExport($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Delimiter is empty or not a string between [\',\', \';\', \'TAB\']', $responseBody->errors);

        $args['format'] = 'pd';
        $fullRequest = $this->createRequestWithBody('PUT', $args);
        $response = $ExportController->updateExport($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Data format is empty or not a string between [\'pdf\', \'csv\']', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    /**
     * @throws Exception
     */
    public function testTheDocumentIsOutOfPerimeterDuringExportButTheStatusCanBeRead(): void
    {
        $this->connectAsUser('cchaplin');
        $resId = $this->createResource();
        $this->connectAsUser('bboule');

        $exportController = new ExportController();
        $args = [
            "id"        => 4,
            "resources" => [$resId],
            "delimiter" => ';',
            "format"    => 'csv',
            "data"      => [
                [
                    "value"      => "subject",
                    "label"      => "Sujet",
                    "isFunction" => false
                ],
                [
                    "value"      => "getStatus",
                    "label"      => "Status",
                    "isFunction" => true
                ],
                [
                    "value"      => "getPriority",
                    "label"      => "Priorité",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDetailLink",
                    "label"      => "Lien page détaillée",
                    "isFunction" => true
                ],
                [
                    "value"      => "getInitiatorEntity",
                    "label"      => "Entité initiatrice",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntity",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntityType",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getCategory",
                    "label"      => "Catégorie",
                    "isFunction" => true
                ]
            ]
        ];
        $this->removePrivilegesGroups($args['id'], self::$privilegeId);

        $fullRequest = $this->createRequestWithBody('PUT', $args);

        $response = $exportController->updateExport($fullRequest, new Response());
        $csvValues = $this->getCsvFromResponse($response);
        $status = $csvValues['Status'];
        $newLetter = 'Nouveau courrier pour le service';

        $this->assertSame($newLetter, $status);
    }

    /**
     * @throws Exception
     */
    public function testTheDocumentIsOutOfPerimeterDuringExportAndOutOfScopeIsDisplayedOnUnavailableFields(): void
    {
        $this->connectAsUser('cchaplin');
        $resId = $this->createResource();
        $this->connectAsUser('jjane');

        $exportController = new ExportController();
        $args = [
            "id"        => 4,
            "resources" => [$resId],
            "delimiter" => ';',
            "format"    => 'csv',
            "data"      => [
                [
                    "value"      => "subject",
                    "label"      => "Sujet",
                    "isFunction" => false
                ],
                [
                    "value"      => "getStatus",
                    "label"      => "Status",
                    "isFunction" => true
                ],
                [
                    "value"      => "getPriority",
                    "label"      => "Priorité",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDetailLink",
                    "label"      => "Lien page détaillée",
                    "isFunction" => true
                ],
                [
                    "value"      => "getInitiatorEntity",
                    "label"      => "Entité initiatrice",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntity",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntityType",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getCategory",
                    "label"      => "Catégorie",
                    "isFunction" => true
                ]
            ]
        ];
        $this->removePrivilegesGroups($args['id'], self::$privilegeId);

        $fullRequest = $this->createRequestWithBody('PUT', $args);

        $response = $exportController->updateExport($fullRequest, new Response());
        $csvValues = $this->getCsvFromResponse($response);
        $values[] = $csvValues['Priorité'];
        $values[] = $csvValues['Entité traitante'];
        $values[] = $csvValues['Catégorie'];

        $outsidePerimeter = 'Hors périmètre';

        foreach ($values as $value) {
            $this->assertSame($outsidePerimeter, $value);
        }
    }

    /**
     * @throws Exception
     */
    public function testTheDocumentIsOutOfPerimeterDuringExportButTheUserHaveTheRightToSeeTheDocument(): void
    {
        $this->connectAsUser('cchaplin');
        $resId = $this->createResource();
        $this->connectAsUser('jjane');

        $exportController = new ExportController();
        $args = [
            "id"        => 4,
            "resources" => [$resId],
            "delimiter" => ';',
            "format"    => 'csv',
            "data"      => [
                [
                    "value"      => "subject",
                    "label"      => "Sujet",
                    "isFunction" => false
                ],
                [
                    "value"      => "getStatus",
                    "label"      => "Status",
                    "isFunction" => true
                ],
                [
                    "value"      => "getPriority",
                    "label"      => "Priorité",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDetailLink",
                    "label"      => "Lien page détaillée",
                    "isFunction" => true
                ],
                [
                    "value"      => "getInitiatorEntity",
                    "label"      => "Entité initiatrice",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntity",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getDestinationEntityType",
                    "label"      => "Entité traitante",
                    "isFunction" => true
                ],
                [
                    "value"      => "getCategory",
                    "label"      => "Catégorie",
                    "isFunction" => true
                ]
            ]
        ];

        $fullRequest = $this->createRequestWithBody('PUT', $args);

        $response = $exportController->updateExport($fullRequest, new Response());
        $csvValues = $this->getCsvFromResponse($response);
        $priority = $csvValues['Priorité'];
        $ValueCheck = 'Normal';

        $this->assertSame($ValueCheck, $priority);
    }

    // Function
    private function createResource(): int
    {
        $resController = new ResController();
        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);
        $body = [
            'modelId'          => 2,
            'status'           => 'NEW',
            'encodedFile'      => $encodedFile,
            'format'           => 'txt',
            'confidentiality'  => false,
            'documentDate'     => '2019-01-01 17:18:47',
            'arrivalDate'      => '2019-01-01 17:18:47',
            'processLimitDate' => '2029-01-01',
            'doctype'          => 102,
            'destination'      => 15,
            'initiator'        => 15,
            'subject'          => 'Breaking News : Superman is alive - PHP unit',
            'typist'           => 19,
            'priority'         => 'poiuytre1357nbvc',
            'folders'          => [1, 16],
        ];
        $fullRequest = $this->createRequestWithBody('POST', $body);
        $response = $resController->create($fullRequest, new Response());
        $responseBody = json_decode((string)$response->getBody());
        $resId = $responseBody->resId;
        self::$resourcesToRemove[] = $resId;

        return $resId;
    }

    private function getCsvFromResponse(Response $response): array
    {
        $responseBody = $response->getBody();
        $responseBody->rewind();
        $stream = $responseBody->detach();
        $csvHeader = fgetcsv($stream, 0, ';');
        $csvHeader = $this->encodeUtf8Csv($csvHeader);
        $csv = fgetcsv($stream, 0, ';');
        $csv = $this->encodeUtf8Csv($csv);
        $line = [];
        foreach ($csvHeader as $key => $value) {
            $line[$value] = $csv[$key];
        }

        return $line;
    }

    private function encodeUtf8Csv(array $ToEncode): array
    {
        return array_map
        (
            function ($ToEncode) {
                return mb_convert_encoding($ToEncode, "UTF-8", "ISO-8859-1");
            },
            $ToEncode
        );
    }

    private function removePrivilegesGroups(int $id, string $privilegeId): void
    {
        self::$group = GroupModel::getById(['id' => $id]);
        PrivilegeModel::removePrivilegeToGroup(['privilegeId' => $privilegeId, 'groupId' => self::$group['group_id']]);
    }

}
