<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Application;

use ExternalSignatoryBook\Application\DocumentLink;
use ExternalSignatoryBook\pastell\Application\PastellConfigurationCheck;
use ExternalSignatoryBook\pastell\Application\RetrieveFromPastell;
use ExternalSignatoryBook\pastell\Domain\PastellConfig;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\ParseIParapheurLogMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\PastellApiMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\PastellConfigMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\ProcessVisaWorkflowSpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\ResourceDataMock;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\AttachmentRepositorySpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\HistoryRepositorySpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Pastell\Mock\HistoryRepositorySpy as PastellHistoryRepositorySpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\ResourceRepositorySpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

class RetrieveFromPastellTest extends TestCase
{
    private PastellApiMock $pastellApiMock;
    private PastellConfigMock $pastellConfigMock;
    private ParseIParapheurLogMock $parseIParapheurLogMock;
    private RetrieveFromPastell $retrieveFromPastell;
    private PastellHistoryRepositorySpy $pastellHistoryRepositorySpy;

    protected function setUp(): void
    {
        $this->pastellApiMock = new PastellApiMock();
        $processVisaWorkflowSpy = new ProcessVisaWorkflowSpy();
        $this->pastellConfigMock = new PastellConfigMock();
        $pastellConfigurationCheck = new PastellConfigurationCheck($this->pastellApiMock, $this->pastellConfigMock);
        $resourceDataMock = new ResourceDataMock();
        $this->pastellHistoryRepositorySpy = new PastellHistoryRepositorySpy();

        $this->parseIParapheurLogMock = new ParseIParapheurLogMock(
            $this->pastellApiMock,
            $this->pastellConfigMock,
            $pastellConfigurationCheck,
            $processVisaWorkflowSpy
        );

        $documentLink = new DocumentLink(
            new UserRepositoryMock(),
            new ResourceRepositorySpy(),
            new AttachmentRepositorySpy(),
            new HistoryRepositorySpy()
        );

        $this->retrieveFromPastell = new RetrieveFromPastell(
            $this->pastellApiMock,
            $this->pastellConfigMock,
            $pastellConfigurationCheck,
            $this->parseIParapheurLogMock,
            $resourceDataMock,
            $this->pastellHistoryRepositorySpy,
            $documentLink
        );
    }

    public function testRetrieveResourceThatDoesNotExist(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];

        $idsToRetrieve = [
            12 => [
                'res_id'      => 12,
                'subject'     => 'Breaking News : Superman is alive - Phpunit',
                'external_id' => 'blabla'
            ]
        ];
        $documentType = 'resLetterbox';

        $this->pastellApiMock->doesFolderExist = false;

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                12 => "Error when getting folder detail : Le document blabla n'appartient pas à l'entité {$this->pastellConfigMock->pastellConfig->getEntity()}"
            ],
            $result['error']
        );
    }

    public function testRetrieveAttachmentThatDoesNotExist(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];

        $idsToRetrieve = [
            12 => [
                'res_id'      => 12,
                'title'       => 'Breaking News : Superman is alive - Phpunit PJ',
                'external_id' => 'blabla'
            ]
        ];
        $documentType = 'noVersion';

        $this->pastellApiMock->doesFolderExist = false;

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                12 => "Error when getting folder detail : Le document blabla n'appartient pas à l'entité {$this->pastellConfigMock->pastellConfig->getEntity()}"
            ],
            $result['error']
        );
    }

    /**
     * @return void
     */
    public function testRetrieveOneResourceWithErrorAndOneSigned(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];

        $idsToRetrieve = [
            12 => [
                'res_id'      => 12,
                'external_id' => 'blabla'
            ],
            42 => [
                'res_id'      => 42,
                'external_id' => 'djqfdh'
            ]
        ];
        $documentType = 'resLetterbox';

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                42 => [
                    'res_id'      => 42,
                    'external_id' => 'djqfdh',
                    'status'      => 'validated',
                    'format'      => 'pdf',
                    'encodedFile' => 'toto'
                ],
            ],
            $result['success']
        );
        $this->assertSame(
            [
                12 => 'Error when getting folder detail : An error occurred !'
            ],
            $result['error']
        );

        $this->assertTrue($this->pastellHistoryRepositorySpy->historyAdded);
    }

    /**
     * @return void
     */
    public function testRetrieveOneResourceFoundButNotFinishOneSignedAndOneRefused(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];

        $idsToRetrieve = [
            12  => [
                'res_id'      => 12,
                'external_id' => 'bloblo'
            ],
            42  => [
                'res_id'      => 42,
                'external_id' => 'djqfdh'
            ],
            152 => [
                'res_id'      => 152,
                'external_id' => 'chuchu'
            ]
        ];
        $documentType = 'resLetterbox';

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                12  => [
                    'res_id'      => 12,
                    'external_id' => 'bloblo',
                    'status'      => 'waiting',
                ],
                42  => [
                    'res_id'      => 42,
                    'external_id' => 'djqfdh',
                    'status'      => 'validated',
                    'format'      => 'pdf',
                    'encodedFile' => 'toto'
                ],
                152 => [
                    'res_id'      => 152,
                    'external_id' => 'chuchu',
                    'status'      => 'refused',
                    'content'     => 'Un nom : une note'
                ]
            ],
            $result['success']
        );

        $this->assertFalse($this->pastellHistoryRepositorySpy->historyAdded);
    }

    /**
     * @return void
     */
    public function testWhenVerificationFailedForAResourceWeRetrieveTheErrorAndTheOtherResources(): void
    {
        $this->pastellApiMock->verificationIparapheurFailedId = 'testKO';

        $idsToRetrieve = [
            420 => [
                'res_id'      => 420,
                'external_id' => 'testKO'
            ],
            42  => [
                'res_id'      => 42,
                'external_id' => 'djqfdh'
            ]
        ];
        $documentType = 'resLetterbox';

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                'success' => [
                    42 => [
                        'res_id'      => 42,
                        'external_id' => 'djqfdh',
                        'status'      => 'validated',
                        'format'      => 'pdf',
                        'encodedFile' => 'toto'
                    ]
                ],
                'error'   => [
                    420 => 'Action "verif-iparapheur" failed'
                ],
            ],
            $result
        );

        $this->assertTrue($this->pastellHistoryRepositorySpy->historyAdded);
    }

    /**
     * @return void
     */
    public function testWhenParsingTheHistoryFailedForAResourceWeRetrieveTheErrorAndTheOtherResources(): void
    {
        $this->parseIParapheurLogMock->errorResId = 420;

        $idsToRetrieve = [
            420 => [
                'res_id'      => 420,
                'external_id' => 'testKO'
            ],
            42  => [
                'res_id'      => 42,
                'external_id' => 'djqfdh'
            ]
        ];
        $documentType = 'resLetterbox';

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                'success' => [
                    42 => [
                        'res_id'      => 42,
                        'external_id' => 'djqfdh',
                        'status'      => 'validated',
                        'format'      => 'pdf',
                        'encodedFile' => 'toto'
                    ]
                ],
                'error'   => [
                    420 => 'Could not parse log'
                ],
            ],
            $result
        );

        $this->assertTrue($this->pastellHistoryRepositorySpy->historyAdded);
    }

    /**
     * @return void
     */
    public function testCannotRetrieveResourcesFromPastellIfConfigurationIsNotValid(): void
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

        $idsToRetrieve = [
            420 => [
                'res_id'      => 420,
                'external_id' => 'testKO'
            ],
            42  => [
                'res_id'      => 42,
                'external_id' => 'djqfdh'
            ]
        ];
        $documentType = 'resLetterbox';

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                'success' => [],
                'error'   => 'Cannot retrieve resources from pastell : pastell configuration is invalid',
            ],
            $result
        );

        $this->assertFalse($this->pastellHistoryRepositorySpy->historyAdded);
    }

    /**
     * @return void
     */
    public function testRetrievingAttachmentUseResIdMaster(): void
    {
        $this->parseIParapheurLogMock->errorResId = 420;

        $idsToRetrieve = [
            43 => [
                'res_id'        => 43,
                'external_id'   => 'testKO',
                'res_id_master' => 420
            ],
            40 => [
                'res_id'        => 40,
                'external_id'   => 'djqfdh',
                'res_id_master' => 42
            ]
        ];
        $documentType = 'noVersion';


        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                'success' => [
                    40 => [
                        'res_id'        => 40,
                        'external_id'   => 'djqfdh',
                        'res_id_master' => 42,
                        'status'        => 'validated',
                        'format'        => 'pdf',
                        'encodedFile'   => 'toto'
                    ]
                ],
                'error'   => [
                    43 => 'Could not parse log'
                ],
            ],
            $result
        );

        $this->assertTrue($this->pastellHistoryRepositorySpy->historyAdded);
    }

    /**
     * @return void
     */
    public function testRetrieveIsNotValidWhenDeleteFolderReturnsAnError(): void
    {
        $this->pastellApiMock->deletedFolder = ['error' => 'An error occurred !'];

        $idsToRetrieve = [
            42 => [
                'res_id'      => 42,
                'external_id' => 'djqfdh',
                'status'      => 'validated'
            ]
        ];
        $documentType = 'resLetterbox';

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                'success' => [],
                'error'   => [42 => 'An error occurred !']
            ], $result);

        $this->assertTrue($this->pastellHistoryRepositorySpy->historyAdded);
    }

    public function testTheSignatoryNameIsTheOneInParapheur(): void
    {
        $this->pastellApiMock->documentsDownload = [
            'encodedFile' => 'toto'
        ];

        $idsToRetrieve = [
            41 => [
                'res_id'      => 41,
                'external_id' => 'djqfdh'
            ]
        ];
        $documentType = 'resLetterbox';

        $result = $this->retrieveFromPastell->retrieve($idsToRetrieve, $documentType);

        $this->assertSame(
            [
                'success' => [
                    41 => [
                        'res_id'      => 41,
                        'external_id' => 'djqfdh',
                        'status'      => 'validated',
                        'format'      => 'pdf',
                        'encodedFile' => 'toto',
                        'signatory'   => 'Bruce Wayne - XELIANS'
                    ]
                ],
                'error'   => [

                ],
            ],
            $result
        );

        $this->assertFalse($this->pastellHistoryRepositorySpy->historyAdded);
    }
}
