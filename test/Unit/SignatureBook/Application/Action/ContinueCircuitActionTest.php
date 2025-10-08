<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief ContinueCircuitActionTest class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Application\Action;

use Exception;
use MaarchCourrier\SignatureBook\Domain\Problem\CurrentTokenIsNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\DataToBeSentToTheParapheurAreEmptyProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\NoDocumentsInSignatureBookForThisId;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureNotAppliedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\CurrentUserInformationsMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action\MaarchParapheurSignatureServiceMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action\SignatureServiceJsonConfigLoaderMock;
use PHPUnit\Framework\TestCase;
use MaarchCourrier\SignatureBook\Application\Action\ContinueCircuitAction;

class ContinueCircuitActionTest extends TestCase
{
    private ContinueCircuitAction $continueCircuitAction;

    private CurrentUserInformationsMock $currentUserRepositoryMock;

    private SignatureServiceJsonConfigLoaderMock $configLoaderMock;

    private MaarchParapheurSignatureServiceMock $signatureServiceMock;

    private array $dataMainDocument = [
        "resId"                  => 1,
        "documentId"             => 4,
        "certificate"            => 'certificate',
        "signatures"             => [
            'signatures1' => 'signature'
        ],
        "hashSignature"          => "a41584bdd99fbfeabc7b45f6fa89a4fa075b3070d44b869af35cea87a1584caa568f696d0c9dabad2481dfb
            bc016fd3562fa009d1b3f3cb31e76adfe5cd5b6026a30d5c1bf78e0d85250bd3709ac45a48276242abf3840f55f00ccbade965c202b
            e107c2df02622974c795bb07537de9a8df6cf0c9497c08f261e89ee4617bec",
        "signatureContentLength" => 30000,
        "signatureFieldName"     => "Signature",
        "tmpUniqueId"            => 4,
        'cookieSession'          => "PHPSESSID=n9dskdn94ndz23nn"
    ];

    private array $dataAttachment = [
        "resId"                  => 1,
        "isAttachment"           => true,
        "documentId"             => 5,
        "certificate"            => 'certificate',
        "signatures"             => [
            'signatures1' => 'signature'
        ],
        "hashSignature"          => "a41584bdd99fbfeabc7b45f6fa89a4fa075b3070d44b869af35cea87a1584caa568f696d0c9dabad2481dfb
            bc016fd3562fa009d1b3f3cb31e76adfe5cd5b6026a30d5c1bf78e0d85250bd3709ac45a48276242abf3840f55f00ccbade965c202b
            e107c2df02622974c795bb07537de9a8df6cf0c9497c08f261e89ee4617bec",
        "signatureContentLength" => 30000,
        "signatureFieldName"     => "Signature",
        "tmpUniqueId"            => 4,
        'cookieSession'          => "PHPSESSID=n9dskdn94ndz23nn"
    ];

    protected function setUp(): void
    {
        $this->currentUserRepositoryMock = new CurrentUserInformationsMock();
        $this->configLoaderMock = new SignatureServiceJsonConfigLoaderMock();
        $this->signatureServiceMock = new MaarchParapheurSignatureServiceMock();
        $this->continueCircuitAction = new ContinueCircuitAction(
            $this->currentUserRepositoryMock,
            $this->signatureServiceMock,
            $this->configLoaderMock,
            true
        );
    }


    /**
     * @return void
     * @throws CurrentTokenIsNotFoundProblem
     * @throws DataToBeSentToTheParapheurAreEmptyProblem
     * @throws NoDocumentsInSignatureBookForThisId
     * @throws SignatureBookNoConfigFoundProblem
     * @throws SignatureNotAppliedProblem
     */
    public function testTheNewInternalParapheurIsEnabled(): void
    {
        $result = $this->continueCircuitAction->execute(
            1,
            ["digitalCertificate" => true, "1" => [$this->dataMainDocument]],
            []
        );
        self::assertTrue($result);
    }

    /**
     * @throws Exception
     */
    public function testCanSignMainDocumentAndAttachmentIfAllDataAreSet(): void
    {
        $result = $this->continueCircuitAction->execute(
            1,
            ["digitalCertificate" => true, "1" => [$this->dataMainDocument, $this->dataAttachment]],
            []
        );
        self::assertTrue($result);
    }

    /**
     * @throws Exception
     */
    public function testCannotSignIfThereIsNotDocumentInDataForSelectedResId(): void
    {
        $this->expectException(NoDocumentsInSignatureBookForThisId::class);
        $this->continueCircuitAction->execute(2, ["1" => [$this->dataMainDocument]], []);
    }

    /**
     * @throws Exception
     */
    public function testCannotSignIfTheSignatureBookConfigIsNotFound(): void
    {
        $this->configLoaderMock->signatureServiceConfigLoader = null;
        $this->expectException(SignatureBookNoConfigFoundProblem::class);
        $this->continueCircuitAction->execute(1, ["1" => [$this->dataMainDocument]], []);
    }

    /**
     * @throws Exception
     */
    public function testCannotSignIfNoTokenIsFound(): void
    {
        $this->currentUserRepositoryMock->token = '';
        $this->expectException(CurrentTokenIsNotFoundProblem::class);
        $this->continueCircuitAction->execute(1, ["1" => [$this->dataMainDocument]], []);
    }

    /**
     * @return void
     * @throws CurrentTokenIsNotFoundProblem
     * @throws DataToBeSentToTheParapheurAreEmptyProblem
     * @throws NoDocumentsInSignatureBookForThisId
     * @throws SignatureBookNoConfigFoundProblem
     * @throws SignatureNotAppliedProblem
     */
    public function testCannotSignIfDuringTheApplicationOfTheSignatureAnErrorOccurred(): void
    {
        $this->signatureServiceMock->applySignature = ['errors' => 'An error has occurred'];
        $this->expectException(SignatureNotAppliedProblem::class);
        $this->continueCircuitAction->execute(1, ["digitalCertificate" => true, "1" => [$this->dataMainDocument]], []);
    }

    /**
     * @throws Exception
     */
    public function testCannotSignIfMandatoryDataIsEmpty(): void
    {
        $dataMainDocument = [
            "documentId"             => 4,
            "certificate"            => 'certificate',
            "signatures"             => [],
            "hashSignature"          => "",
            "signatureContentLength" => 0,
            "signatureFieldName"     => "",
            "tmpUniqueId"            => 4,
            'cookieSession'          => "n9dskdn94ndz23nn"
        ];
        $this->expectException(DataToBeSentToTheParapheurAreEmptyProblem::class);
        $this->expectExceptionObject(
            new DataToBeSentToTheParapheurAreEmptyProblem(
                ['resId', 'hashSignature, signatureContentLength, signatureFieldName']
            )
        );
        $this->continueCircuitAction->execute(1, ["digitalCertificate" => true, "1" => [$dataMainDocument]], []);
    }
}
