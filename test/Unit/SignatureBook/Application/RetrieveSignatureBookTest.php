<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   Retrieve Signature Book Test
 * @author  dev@maarch.org
 */

namespace MaarchCourrier\Tests\Unit\SignatureBook\Application;

use MaarchCourrier\Attachment\Domain\Attachment;
use MaarchCourrier\Attachment\Domain\AttachmentType;
use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\DocumentStorage\Domain\Document;
use MaarchCourrier\MainResource\Domain\Integration;
use MaarchCourrier\MainResource\Domain\MainResource;
use MaarchCourrier\SignatureBook\Application\RetrieveSignatureBook;
use MaarchCourrier\SignatureBook\Domain\Problem\MainResourceDoesNotExistInSignatureBookBasketProblem;
use MaarchCourrier\SignatureBook\Domain\SignatureBookResource;
use MaarchCourrier\Tests\Unit\Attachment\Mock\AttachmentRepositoryMock;
use MaarchCourrier\Tests\Unit\Authorization\Mock\MainResourcePerimeterCheckerServiceMock;
use MaarchCourrier\Tests\Unit\Authorization\Mock\PrivilegeCheckerMock;
use MaarchCourrier\Tests\Unit\DocumentConversion\Mock\ConvertPdfServiceMock;
use MaarchCourrier\Tests\Unit\DocumentConversion\Mock\SignatureMainDocumentRepositoryMock;
use MaarchCourrier\Tests\Unit\MainResource\Mock\MainResourceRepositoryMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\CurrentUserInformationsMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Repository\VisaWorkflowRepositoryMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\SignatureBookRepositoryMock;
use MaarchCourrier\User\Domain\User;
use PHPUnit\Framework\TestCase;

class RetrieveSignatureBookTest extends TestCase
{
    private RetrieveSignatureBook $retrieveSignatureBook;
    private MainResourceRepositoryMock $mainResourceRepositoryMock;
    private CurrentUserInformationsMock $currentUserInformationsMock;
    private MainResourcePerimeterCheckerServiceMock $mainResourceAccessControlServiceMock;
    private SignatureBookRepositoryMock $signatureBookRepositoryMock;
    private SignatureMainDocumentRepositoryMock $signatureMainDocumentRepositoryMock;
    private ConvertPdfServiceMock $convertPdfServiceMock;
    private AttachmentRepositoryMock $attachmentRepositoryMock;
    private PrivilegeCheckerMock $privilegeCheckerMock;
    private VisaWorkflowRepositoryMock $visaWorkflowRepositoryMock;

    private User $user;

    protected function setUp(): void
    {
        $this->mainResourceRepositoryMock = new MainResourceRepositoryMock();
        $this->currentUserInformationsMock = new CurrentUserInformationsMock();
        $this->mainResourceAccessControlServiceMock = new MainResourcePerimeterCheckerServiceMock();
        $this->signatureBookRepositoryMock = new SignatureBookRepositoryMock();
        $this->signatureMainDocumentRepositoryMock = new SignatureMainDocumentRepositoryMock();
        $this->convertPdfServiceMock = new ConvertPdfServiceMock();

        $this->attachmentRepositoryMock = new AttachmentRepositoryMock();
        $this->privilegeCheckerMock = new PrivilegeCheckerMock();
        $this->visaWorkflowRepositoryMock = new VisaWorkflowRepositoryMock();

        $this->user = new User();
        $this->user->setId(1);
        $document = (new Document())
            ->setFileName('the-file.pdf')
            ->setFileExtension('pdf');
        $integration = (new Integration())->setInSignatureBook(false);
        $this->mainResourceRepositoryMock->mainResource = (new MainResource())
            ->setResId(42)
            ->setSubject('Courrier Test')
            ->setTypist($this->user)
            ->setChrono('MAARCH/2024/1')
            ->setDocument($document)
            ->setIntegration($integration);

        $this->attachmentRepositoryMock->attachmentsInSignatureBook = $this->makeAttachmentList($document);

        $this->retrieveSignatureBook = new RetrieveSignatureBook(
            $this->mainResourceRepositoryMock,
            $this->currentUserInformationsMock,
            $this->mainResourceAccessControlServiceMock,
            $this->signatureBookRepositoryMock,
            $this->signatureMainDocumentRepositoryMock,
            $this->convertPdfServiceMock,
            $this->attachmentRepositoryMock,
            $this->privilegeCheckerMock,
            $this->visaWorkflowRepositoryMock
        );
    }

    private function makeAttachmentList(Document $document): array
    {
        $list = [];
        $signableAttachmentType = (new AttachmentType())
            ->setType('response_project')
            ->setLabel('Projet de réponse')
            ->setSignable(true);
        $nonSignableAttachmentType = (new AttachmentType())
            ->setType('simple_attachment')
            ->setLabel('Pièce jointe')
            ->setSignable(false);

        $list[] = (new Attachment())
            ->setResId(1)
            ->setTitle('Demande de document')
            ->setChrono('MAARCH/2024/1')
            ->setMainResource($this->mainResourceRepositoryMock->mainResource)
            ->setTypist($this->user)
            ->setRelation(1)
            ->setType($signableAttachmentType)
            ->setDocument($document);
        $list[] = (new Attachment())
            ->setResId(2)
            ->setTitle('Piece identité')
            ->setChrono('MAARCH/2024/2')
            ->setMainResource($this->mainResourceRepositoryMock->mainResource)
            ->setTypist($this->user)
            ->setRelation(1)
            ->setType($nonSignableAttachmentType)
            ->setDocument($document);

        return $list;
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotGetMainResourceWhenResourceDoesNotExist(): void
    {
        $this->mainResourceRepositoryMock->mainResource = null;
        $this->expectExceptionObject(new ResourceDoesNotExistProblem());
        $this->retrieveSignatureBook->getSignatureBook(100);
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotAccessSignatureBookWhenUserHasNoRightAccess(): void
    {
        $this->mainResourceAccessControlServiceMock->doesUserHasRight = false;
        $this->expectExceptionObject(new MainResourceOutOfPerimeterProblem());
        $this->retrieveSignatureBook->getSignatureBook(100);
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotGetSignatureBookResourceIfNotInSignatureBookBasket(): void
    {
        $this->signatureBookRepositoryMock->isInSignatureBookBasket = false;
        $this->expectExceptionObject(new MainResourceDoesNotExistInSignatureBookBasketProblem());
        $this->retrieveSignatureBook->getSignatureBook(100);
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanUpdateDocumentsInSignatureBookBasketWhenBasketParamIsEnable(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $this->assertTrue($signatureBook->isCanUpdateResources());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotUpdateDocumentsInSignatureBookBasketWhenBasketParamIsDisable()
    {
        $this->signatureBookRepositoryMock->canUpdateResourcesInSignatureBook = false;
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $this->assertFalse($signatureBook->isCanUpdateResources());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testGetMainResourceInResourcesToSignWhenIntegratedInSignatoryBook(): void
    {
        $integration = (new Integration())->setInSignatureBook(true);
        $this->mainResourceRepositoryMock->mainResource->setIntegration($integration);

        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertNotEmpty($signatureBook->getResourcesToSign());
        $this->assertSame('main_document', $signatureBook->getResourcesToSign()[0]->getType());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testGetMainResourceInResourcesAttachedWhenNotIntegratedInSignatoryBook(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $this->assertSame('main_document', $resourcesAttached[0]->getType());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanModifyMainResourceWhenNotIntegratedInSignatoryBookAndSignatureBookParamIsEnable(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($lastResourceAttached->isCanModify());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanModifyMainResourceWhenNotIntegratedAndSignatureBookParamDisabledAndCurrentUserIsDocCreator(): void
    {
        $this->currentUserInformationsMock->userId = 1;
        $this->signatureBookRepositoryMock->canUpdateResourcesInSignatureBook = false;

        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($lastResourceAttached->isCanModify());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotModifyMainResourceWhenNotIntegratedAndSignatureBookParamDisabledAndCurrentUserIsNotDocCreator(): void
    {
        $this->signatureBookRepositoryMock->canUpdateResourcesInSignatureBook = false;
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertFalse($lastResourceAttached->isCanModify());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotDeleteMainResourceInSignatureBook(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertFalse($resourcesAttached[0]->isCanDelete());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanGetASignableAttachmentInSignatureBookFromMainResource(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertNotEmpty($signatureBook->getResourcesToSign());
        $this->assertSame('response_project', $signatureBook->getResourcesToSign()[0]->getType());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanGetANonSignableAttachmentInSignatureBookFromMainResource(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertSame('simple_attachment', $signatureBook->getResourcesAttached()[1]->getType());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testIsMainDocumentConvertedInSignatureBook(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($signatureBook->getResourcesAttached()[0]->isConverted());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testIsASignableAttachmentDocumentConvertedInSignatureBook(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertNotEmpty($signatureBook->getResourcesToSign());
        $this->assertTrue($signatureBook->getResourcesToSign()[0]->isConverted());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testIsANonSignableAttachmentDocumentConvertedInSignatureBook(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($signatureBook->getResourcesAttached()[1]->isConverted());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanModifyANonSignableAttachmentWhenSignatureBookParamIsEnable(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($lastResourceAttached->isCanModify());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanModifyANonSignableAttachmentWhenSignatureBookParamDisabledAndCurrentUserIsDocCreator(): void
    {
        $this->currentUserInformationsMock->userId = 1;
        $this->signatureBookRepositoryMock->canUpdateResourcesInSignatureBook = false;

        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($lastResourceAttached->isCanModify());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotModifyANonSignableAttachmentWhenSignatureBookParamDisabledAndCurrentUserIsNotDocCreator(): void
    {
        $this->signatureBookRepositoryMock->canUpdateResourcesInSignatureBook = false;

        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertFalse($lastResourceAttached->isCanModify());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanDeleteANonSignableAttachmentWhenSignatureBookParamIsEnable(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($lastResourceAttached->isCanDelete());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCanDeleteANonSignableAttachmentWhenSignatureBookParamDisabledAndCurrentUserIsDocCreator(): void
    {
        $this->currentUserInformationsMock->userId = 1;
        $this->signatureBookRepositoryMock->canUpdateResourcesInSignatureBook = false;

        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertTrue($lastResourceAttached->isCanDelete());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testCannotDeleteANonSignableAttachmentWhenSignatureBookParamDisabledAndCurrentUserIsNotDocCreator(): void
    {
        $this->signatureBookRepositoryMock->canUpdateResourcesInSignatureBook = false;

        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $resourcesAttached = $signatureBook->getResourcesAttached();
        $lastResourceAttached = end($resourcesAttached);

        $this->assertNotEmpty($signatureBook->getResourcesAttached());
        $this->assertFalse($lastResourceAttached->isCanDelete());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testDoesSignatureBookResourceHasAnActiveWorkflow(): void
    {
        $this->visaWorkflowRepositoryMock->isActiveWorkflow = true;
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $this->assertTrue($signatureBook->isHasActiveWorkflow());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testIsCurrentUserTheSameInWorkflowStep(): void
    {
        $this->currentUserInformationsMock->userId = 1;
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $this->assertTrue($signatureBook->isCurrentWorkflowUser());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testIsCurrentUserHasSignDocumentPrivilege(): void
    {
        $this->privilegeCheckerMock->hasPrivilege = true;
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);
        $this->assertTrue($signatureBook->isCanSignResources());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testGetSignatureBookWithNoResourcesToSign(): void
    {
        $this->signatureMainDocumentRepositoryMock->mainDocumentIsSigned = true;
        $this->attachmentRepositoryMock->attachmentsInSignatureBook = [];

        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertEmpty($signatureBook->getResourcesToSign());
        $this->assertNotEmpty($signatureBook->getResourcesAttached());
    }

    /**
     * @return void
     * @throws MainResourceDoesNotExistInSignatureBookBasketProblem
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     */
    public function testGetSignatureBookWhenNoProblemOccurred(): void
    {
        $signatureBook = $this->retrieveSignatureBook->getSignatureBook(100);

        $this->assertContainsOnlyInstancesOf(SignatureBookResource::class, $signatureBook->getResourcesToSign());

        $this->assertContainsOnlyInstancesOf(SignatureBookResource::class, $signatureBook->getResourcesAttached());
    }
}
