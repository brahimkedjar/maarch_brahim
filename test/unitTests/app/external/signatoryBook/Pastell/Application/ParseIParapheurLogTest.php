<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Application;

use Exception;
use ExternalSignatoryBook\pastell\Application\ParseIParapheurLog;
use ExternalSignatoryBook\pastell\Application\PastellConfigurationCheck;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\PastellApiMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\PastellConfigMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\ProcessVisaWorkflowSpy;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use stdClass;

class ParseIParapheurLogTest extends TestCase
{
    private PastellApiMock $pastellApiMock;
    private ProcessVisaWorkflowSpy $processVisaWorkflow;
    private ParseIParapheurLog $parseIParapheurLog;

    protected function setUp(): void
    {
        $this->pastellApiMock = new PastellApiMock();
        $this->pastellApiMock->journalXml = new stdClass();
        $this->pastellApiMock->journalXml->MessageRetour = new stdClass();
        $this->pastellApiMock->journalXml->LogDossier = new stdClass();
        $this->pastellApiMock->journalXml->LogDossier->LogDossier = [new stdClass(), new stdClass()];
        $this->processVisaWorkflow = new ProcessVisaWorkflowSpy();
        $this->pastellConfigMock = new PastellConfigMock();
        $pastellConfigCheck = new PastellConfigurationCheck($this->pastellApiMock, $this->pastellConfigMock);
        $this->parseIParapheurLog = new ParseIParapheurLog(
            $this->pastellApiMock,
            $this->pastellConfigMock,
            $pastellConfigCheck,
            $this->processVisaWorkflow
        );
    }

    /**
     * @return void
     */
    public function testParseLogIparapheurReturnCodeIsAnError(): void
    {
        $this->pastellApiMock->journalXml->MessageRetour->codeRetour = 'KO';
        $this->pastellApiMock->journalXml->MessageRetour->severite = 'INFO';
        $this->pastellApiMock->journalXml->MessageRetour->message = 'error';
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->parseLogIparapheur($resId, $idFolder);

        $this->assertSame(['error' => 'Log KO in iParapheur : [INFO] error'], $result);
    }

    /**
     * @return array[]
     */
    public function validatedStateProvider(): array
    {
        return [
            'visa' => ['VisaOK'],
            'sign' => ['CachetOK'],
        ];
    }

    /**
     * @dataProvider validatedStateProvider
     */
    public function testParseLogIparapheurDocumentIsValidated(string $state): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[0]->status = 'toto';
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->status = $state;
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->nom = 'tata';
        $this->pastellApiMock->journalXml->MessageRetour->codeRetour = 'OK';
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->parseLogIparapheur($resId, $idFolder);

        $this->assertSame(
            [
                'status'      => 'validated',
                'format'      => 'pdf',
                'encodedFile' => 'toto',
                'signatory'   => 'tata'
            ],
            $result
        );
    }

    /**
     * @return array[]
     */
    public function refusedStateProvider(): array
    {
        return [
            'visa' => ['RejetVisa'],
            'sign' => ['RejetCachet']
        ];
    }

    /**
     * @dataProvider refusedStateProvider
     */
    public function testParseLogIparapheurDocumentIsRefused(string $state): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[0]->status = 'view'; // Status not validated and not refused
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->status = $state;
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->nom = 'Nom';
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->annotation = 'annotation';
        $this->pastellApiMock->journalXml->MessageRetour->codeRetour = 'OK';
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->parseLogIparapheur($resId, $idFolder);

        $this->assertSame(
            [
                'status'    => 'refused',
                'content'   => 'Nom : annotation',
                'signatory' => 'Nom'
            ],
            $result
        );
    }

    /**
     * @dataProvider refusedStateProvider
     * @param string $state
     * @return void
     */
    public function testParseLogIParapheurDocumentIsRefusedAndHistoryLogIsXmlElement(string $state): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[0]->status = 'view';
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->status = $state;
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->nom = new SimpleXMLElement('<nom>Bruce Wayne - XELIANS</nom>');
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->annotation = new SimpleXMLElement('<annotation>Je refuse</annotation>');
        $this->pastellApiMock->journalXml->MessageRetour->codeRetour = 'OK';
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->parseLogIparapheur($resId, $idFolder);

        $this->assertSame(
            [
                'status'    => 'refused',
                'content'   => 'Bruce Wayne - XELIANS : Je refuse',
                'signatory' => 'Bruce Wayne - XELIANS'
            ],
            $result
        );
    }

    /**
     * @return void
     */
    public function testParseLogIparapheurDocumentIsNotRefusedAndNotValidated(): void
    {
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[0]->status = 'toto';
        $this->pastellApiMock->journalXml->LogDossier->LogDossier[1]->status = 'blabla';
        $this->pastellApiMock->journalXml->MessageRetour->codeRetour = 'OK';
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->parseLogIparapheur($resId, $idFolder);

        $this->assertSame(
            [
                'status' => 'waiting',
            ],
            $result
        );
    }

    /**
     * @return void
     */
    public function testParseLogIparapheurXmlDetailReturnAnError(): void
    {
        $this->pastellApiMock->journalXml->error = 'Erreur';
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->parseLogIparapheur($resId, $idFolder);

        $this->assertSame(
            [
                'error' => 'Erreur',
            ],
            $result
        );
    }

    /**
     * @return void
     */
    public function testHandleValidateVisaWorkFlowIsCalledIfIsSigned(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];
        $resId = 42;
        $idFolder = 'djqfdh';

        $this->parseIParapheurLog->handleValidate($resId, $idFolder, true);

        $this->assertTrue($this->processVisaWorkflow->processVisaWorkflowCalled);
    }

    /**
     * @return void
     */
    public function testHandleValidateVisaWorkFlowIsNotCalledIfIsNotSigned(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];
        $resId = 42;
        $idFolder = 'djqfdh';

        $this->parseIParapheurLog->handleValidate($resId, $idFolder, false);

        $this->assertFalse($this->processVisaWorkflow->processVisaWorkflowCalled);
    }

    /**
     * @return void
     */
    public function testHandleValidateTheDownloadFileReturnAnError(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'error' => 'Je suis ton erreur'
        ];
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->handleValidate($resId, $idFolder, true);

        $this->assertSame(['error' => 'Je suis ton erreur'], $result);
    }

    /**
     * @return void
     */
    public function testHandleRefusedRetrieveTheNoteContent(): void
    {
        $result = $this->parseIParapheurLog->handleRefused('Un nom', 'une note');

        $this->assertNotEmpty($result);
        $this->assertSame(
            [
                'status'  => 'refused',
                'content' => 'Un nom : une note',
            ],
            $result
        );
    }

    /**
     * @return void
     */
    public function testHandleValidateRetrieveTheFileInBase64(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];
        $resId = 42;
        $idFolder = 'djqfdh';

        $result = $this->parseIParapheurLog->handleValidate($resId, $idFolder, false);

        $this->assertNotEmpty($result);
        $this->assertSame(
            [
                'status'      => 'validated',
                'format'      => 'pdf',
                'encodedFile' => 'toto'
            ],
            $result
        );
    }
}
