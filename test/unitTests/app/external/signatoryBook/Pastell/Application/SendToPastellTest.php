<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Application;

use ExternalSignatoryBook\pastell\Application\PastellConfigurationCheck;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\PastellApiMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\PastellConfigMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\ProcessVisaWorkflowSpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\ResourceDataMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\ResourceFileMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\SendToPastellSpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\VisaCircuitDataMock;
use PHPUnit\Framework\TestCase;

class SendToPastellTest extends TestCase
{
    private PastellApiMock $pastellApiMock;

    private PastellConfigMock $pastellConfigMock;

    private ResourceDataMock $resourceData;

    private ResourceFileMock $resourceFile;

    private SendToPastellSpy $sendToPastell;

    private VisaCircuitDataMock $visaCircuitData;


    protected function setUp(): void
    {
        $this->pastellApiMock = new PastellApiMock();
        $this->pastellConfigMock = new PastellConfigMock();
        $pastellConfigCheck = new PastellConfigurationCheck($this->pastellApiMock, $this->pastellConfigMock);
        $this->processVisaWorkflow = new ProcessVisaWorkflowSpy();
        $this->resourceData = new ResourceDataMock();
        $this->resourceFile = new ResourceFileMock();
        $this->visaCircuitData = new VisaCircuitDataMock();
        $this->sendToPastell = new SendToPastellSpy(
            $pastellConfigCheck,
            $this->pastellApiMock,
            $this->pastellConfigMock,
            $this->resourceData,
            $this->resourceFile,
            $this->processVisaWorkflow,
            $this->visaCircuitData
        );
    }


    /*
     * ----------------------------------------------------------------------------------------
     *  Test sendData
     * ----------------------------------------------------------------------------------------
     */

    /**
     * Test sendData when folder created
     * @return void
     */
    public function testSendDataReturnsIdFolderWhenCreated(): void
    {
        $result = $this->sendToPastell->sendData(42);

        $this->assertSame(
            [
                'sended' => [
                    'letterbox_coll'   => [
                        42 => 'hfqvhv' ?? null
                    ],
                    'attachments_coll' => []
                ]
            ],
            $result
        );
    }

    /**
     * Test sendData failed when id folder is missing
     * @return void
     */
    public function testSendDataReturnsAnErrorWhenIdFolderIsMissing(): void
    {
        $this->pastellApiMock->folder = ['error' => 'No folder ID retrieved from Pastell'];

        $result = $this->sendToPastell->sendData(42);

        $this->assertSame(
            [
                'error' => 'No folder ID retrieved from Pastell'
            ],
            $result
        );
    }

    /**
     * Testing sendData failed when Pastell configuration is invalid
     * @return void
     */
    public function testCannotSendDataWhenConfigurationIsInvalid(): void
    {
        $this->pastellConfigMock->pastellConfig = new PastellConfig(
            '',
            '',
            '',
            0,
            0,
            '',
            '',
            '',
            ''
        );

        $result = $this->sendToPastell->sendData(42);

        $this->assertSame(
            [
                'error' => 'Cannot retrieve resources from pastell : pastell configuration is invalid'
            ],
            $result
        );
    }

    /**
     * Testing send Data with next signatory userId as iParapheur subtype
     * @return void
     */
    public function testSendDataUsesNextSignatoryUserIdAsTheSousType(): void
    {
        $this->visaCircuitData->signatoryUserId = 'ppetit';

        $result = $this->sendToPastell->sendData(42);

        $this->assertSame('ppetit', $this->sendToPastell->sousTypeGiven);

        $this->assertSame(
            [
                'sended' => [
                    'letterbox_coll'   => [
                        42 => 'hfqvhv'
                    ],
                    'attachments_coll' => []
                ]
            ],
            $result
        );
    }

    /**
     * Testing sendResource when a signable attachment is sent
     * @return void
     */
    public function testSendResourceWithSignableAttachments(): void
    {
        $this->resourceData->attachmentTypes = [
            'type_signable'     => true,
            'type_not_signable' => false
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 1,
                'attachment_type' => 'type_not_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Not signable PJ'
            ],
            [
                'res_id'          => 2,
                'attachment_type' => 'type_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = '/path/to/attachment.pdf';

        $result = $this->sendToPastell->sendData(42);

        $this->assertSame(
            [
                'sended' => [
                    'letterbox_coll'   => [
                        42 => 'hfqvhv'
                    ],
                    'attachments_coll' => [
                        2 => 'hfqvhv'
                    ]
                ]
            ],
            $result
        );
    }

    /*
     * ----------------------------------------------------------------------------------------
     *  Test sendFolderToPastell
     * ----------------------------------------------------------------------------------------
     */

    /**
     * Testing conf when folder ID is not valid
     * @return void
     */
    public function testConfigurationIsNotValidIfIdFolderIsNotValid(): void
    {
        $this->pastellApiMock->folder = ['error' => 'Erreur lors de la récupération de l\'id du dossier'];

        $resId = 42;
        $title = 'blablabla';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['error' => 'Erreur lors de la récupération de l\'id du dossier'], $result);
    }

    /**
     * Testing when a folder is created returns an idFolder
     * @return void
     */
    public function testSendFolderReturnsIdFolderWhenCreated(): void
    {
        $result = $this->sendToPastell->sendFolderToPastell(42, 'Toto', 'courrier', '/opt/my-document.pdf');

        $this->assertSame(['idFolder' => 'hfqvhv'], $result);
    }

    /**
     * Testing sending datas if id folder is missing
     * @return void
     */
    public function testSendToPastellIsNotValidIfIdFolderIsMissing(): void
    {
        $this->pastellApiMock->folder = [];

        $resId = 42;
        $title = 'blablabla';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['error' => 'Folder creation has failed'], $result);
    }

    /**
     * Testing sending datas with the right id folder
     * @return void
     */
    public function testSendToPastellIsValidIfIdFolderIsNotMissing(): void
    {
        $resId = 42;
        $title = 'blablabla';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['idFolder' => 'hfqvhv'], $result);
    }

    /**
     * Test sending datas when iParapheur subtype returns an error
     * @return void
     */
    public function testSendToPastellIsNotValidIfIparapheurSousTypeReturnAnError(): void
    {
        $this->pastellApiMock->iParapheurSousType = ['error' => 'An error occurred !'];

        $resId = 42;
        $title = 'blablabla';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['error' => 'An error occurred !'], $result);
    }

    /**
     * Test sending datas when iParapheur subtype not found
     * @return void
     */
    public function testSendToPastellIsNotValidIfIparapheurSousTypeIsNotFoundInPastell(): void
    {
        $this->pastellConfigMock->pastellConfig = new PastellConfig(
            'testurl',
            'toto',
            'toto123',
            193,
            776,
            'ls-document-pdf',
            'XELIANS COURRIER',
            'default-do-not-exist',
            ''
        );

        $resId = 42;
        $title = 'blablabla';
        $sousType = 'do-not-exist';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['error' => 'Subtype does not exist in iParapheur'], $result);
    }

    /**
     * Test when using the default iParapheur subtype
     * @return void
     */
    public function testWhenGivenSousTypeDoesNotExistTheDefaultSousTypeIsUsed(): void
    {
        $resId = 42;
        $title = 'blablabla';
        $sousType = 'do-not-exist';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['idFolder' => 'hfqvhv'], $result);
        $this->assertSame('courrier', $this->pastellApiMock->sousTypeUsed);
    }

    /**
     * Test sending datas when iParapheur subtype found in Pastell
     * @return void
     */
    public function testSendToPastellIsValidIfIparapheurSousTypeIsFoundInPastell(): void
    {
        $resId = 42;
        $title = 'blablabla';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['idFolder' => 'hfqvhv'], $result);
    }

    /**
     * Test sending datas failed when edit folder failed
     * @return void
     */
    public function testSendToPastellIsNotSentIfEditFolderFailed(): void
    {
        $this->pastellApiMock->dataFolder = ['error' => 'An error occurred'];

        $resId = 42;
        $title = '';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['error' => 'An error occurred'], $result);
    }

    /**
     * Test sending datas failed when uploading main file failed
     * @return void
     */
    public function testSendToPastellIsNoSentIfUploadingMainFileFailed(): void
    {
        $this->pastellApiMock->mainFile = ['error' => 'An error occurred'];

        $resId = 42;
        $title = 'blablabla';
        $sousType = 'courrier';
        $filePath = '';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['error' => 'An error occurred'], $result);
    }

    /**
     * Test sending datas failed when orientation action failed
     * @return void
     */
    public function testSendToPastellIsNotSentIfOrientationFailed(): void
    {
        $this->pastellApiMock->orientation = ['error' => 'An error occurred'];

        $resId = 42;
        $title = '';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(['error' => 'An error occurred'], $result);
    }

    /**
     * Test sending datas failed when send-iparapheur action failed
     * @return void
     */
    public function testSendToPastellIsNotSentIfSendIparapheurIsNotTrue(): void
    {
        $this->pastellApiMock->sendIparapheur = false;
        $this->pastellApiMock->documentDetails['actionPossibles'] = ['send-iparapheur'];

        $resId = 0;
        $title = '';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath);

        $this->assertSame(
            ['error' => 'L\'action « send-iparapheur »  n\'est pas permise : Le dernier état du document (send-iparapheur) ne permet pas de déclencher cette action'],
            $result
        );
    }

    /**
     * Test when there is more than one annex uploaded
     * @return void
     */
    public function testPastellIsCalledForEveryAnnexUploaded(): void
    {
        $this->pastellApiMock->documentDetails['actionPossibles'] = ['send-iparapheur'];
        $this->pastellApiMock->sendIparapheur = false;

        $resId = 0;
        $title = '';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';
        $annexes = [
            '/path/to/attachment1.pdf',
            '/path/to/attachment2.pdf',
        ];

        $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath, $annexes);

        $this->assertSame(
            [
                [
                    'nb'       => 0,
                    'filePath' => '/path/to/attachment1.pdf'
                ],
                [
                    'nb'       => 1,
                    'filePath' => '/path/to/attachment2.pdf'
                ]
            ],
            $this->pastellApiMock->uploadedAnnexes
        );
    }

    /**
     * Folder is uploaded even when annex upload failed
     * @return void
     */
    public function testWhenAnnexUploadFailsWeUploadTheFolderAnyway(): void
    {
        $this->pastellApiMock->uploadAnnexError = ['error' => 'Error uploading annex'];

        $resId = 0;
        $title = '';
        $sousType = 'courrier';
        $filePath = '/test/toto.pdf';
        $annexes = [
            '/path/to/attachment1.pdf',
            '/path/to/attachment2.pdf',
        ];

        $result = $this->sendToPastell->sendFolderToPastell($resId, $title, $sousType, $filePath, $annexes);

        $this->assertSame([], $this->pastellApiMock->uploadedAnnexes);
        $this->assertSame(['idFolder' => 'hfqvhv'], $result);
    }

    /*
     * ----------------------------------------------------------------------------------------
     *  Test sendResource
     * ----------------------------------------------------------------------------------------
     */

    /**
     * Testing sendResource when main resource doesn't exist
     * @return void
     */
    public function testCannotSendResourceIfMainResourceDoesNotExist(): void
    {
        $this->resourceData->resourceExist = false;
        $resId = 42;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(['error' => 'Resource not found'], $result);
    }

    /**
     * Testing when data is sent to a folder returns an idFolder
     * @return void
     */
    public function testSendResourceReturnsIdFolderWhenCreated(): void
    {
        $resId = 42;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(['attachments' => [], 'resource' => 'hfqvhv'], $result);
    }

    /**
     * Test sendResource when main file extension is not PDF
     * @return void
     */
    public function testSendResourceReturnsErrorWhenMainFileExtensionIsNotPDF(): void
    {
        $this->resourceFile->adrMainInfo = 'Error';

        $resId = 42;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(['error' => 'Document ' . $resId . ' is not converted in pdf'], $result);
    }

    /**
     * Test sendResource when non-signable attachment is sent as an annex
     * @return void
     */
    public function testNonSignableAttachementIsSentAsAnAnnex(): void
    {
        $this->resourceData->attachmentTypes = [
            'type_signable'     => true,
            'type_not_signable' => false
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 1,
                'attachment_type' => 'type_not_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Not signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = '/path/to/attachment.pdf';

        $resId = 0;
        $sousType = 'courrier';

        $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(
            [
                '/path/to/attachment.pdf'
            ],
            $this->sendToPastell->annexes
        );
    }

    /**
     * Test sendResource when signable attachment is sent as a document to sign
     * @return void
     */
    public function testSignableAttachementsAreSentAsADocumentToSign(): void
    {
        $this->resourceData->attachmentTypes = [
            'type_signable'     => true,
            'type_not_signable' => false
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 1,
                'attachment_type' => 'type_not_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Not signable PJ'
            ],
            [
                'res_id'          => 2,
                'attachment_type' => 'type_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = '/path/to/attachment.pdf';

        $resId = 0;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(
            [
                'attachments' => [
                    2 => 'hfqvhv',
                ],
                'resource'    => 'hfqvhv'
            ],
            $result
        );
        $this->assertSame(
            [
                'blabablblalba', // main resource title
                'Signable PJ'
            ],
            $this->sendToPastell->titlesGiven
        );
    }

    /**
     * Test sendResource when main file is an annex and attachments are signable
     * @return void
     */
    public function testMainResourceFileIsSentAsAnnexForSignableAttachments(): void
    {
        $this->resourceData->attachmentTypes = [
            'type_signable' => true
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 2,
                'attachment_type' => 'type_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = '/path/to/attachment.pdf';

        $resId = 0;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(
            [
                'attachments' => [
                    2 => 'hfqvhv',
                ],
                'resource'    => 'hfqvhv'
            ],
            $result
        );
        $this->assertSame(
            [
                'toto.pdf'
            ],
            $this->sendToPastell->annexes
        );
    }

    /**
     * Test sendResource failed when attachments fingerprints do not match
     * @return void
     */
    public function testSendResourceReturnsErrorWhenFingerprintsDoNotMatch(): void
    {
        $this->resourceData->attachmentTypes = [
            'type_signable' => true
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 2,
                'attachment_type' => 'type_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = 'Error: Fingerprints';

        $resId = 0;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(['error' => 'Fingerprints do not match'], $result);
    }

    /**
     * Test sendResource failed when attachments extension is not a PDF
     * @return void
     */
    public function testSendResourceReturnsErrorWhenAttachmentExtensionIsNotPDF(): void
    {
        $this->resourceData->attachmentTypes = [
            'type_signable' => true
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 2,
                'attachment_type' => 'type_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = 'Error: Document';

        $resId = 0;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(['error' => 'Error: Document ' . $this->resourceData->attachments[0]['title'] . ' is not converted in pdf'], $result);
    }

    /**
     * @return void
     */
    public function testSendResourceReturnsAnErrorWhenThereIsASignableAttachmentAndMainDocumentIsNotInSignatoryAndThereIsAnErrorDuringFolderCreation(): void
    {
        $this->resourceData->mainResourceInSignatoryBook = false;
        $this->pastellApiMock->folder = ['error' => 'erreur'];
        $this->resourceData->attachmentTypes = [
            'type_signable' => true
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 2,
                'attachment_type' => 'type_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = '/path/to/attachment.pdf';

        $resId = 0;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame(['Signable PJ'], $this->sendToPastell->titlesGiven);

        $this->assertSame(['error' => 'erreur'], $result);
    }

    /**
     * @return void
     */
    public function testSendResourceReturnsAnEmptyArrayWhenAttachmentIsNotSignableAndMainResourceIsNotInSignatory(): void
    {
        $this->resourceData->mainResourceInSignatoryBook = false;
        $this->resourceData->attachmentTypes = [
            'type_signable' => false
        ];
        $this->resourceData->attachments = [
            [
                'res_id'          => 2,
                'attachment_type' => 'type_signable',
                'fingerprint'     => 'azerty',
                'title'           => 'Signable PJ'
            ]
        ];
        $this->resourceFile->attachmentFilePath = '/path/to/attachment.pdf';

        $resId = 0;
        $sousType = 'courrier';

        $result = $this->sendToPastell->sendResource($resId, $sousType);

        $this->assertSame([], $this->sendToPastell->titlesGiven);

        $this->assertSame([
            'attachments' => [],
            'resource'    => []
        ], $result);
    }
}
