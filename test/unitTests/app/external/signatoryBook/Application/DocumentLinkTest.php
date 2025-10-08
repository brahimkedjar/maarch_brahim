<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

namespace MaarchCourrier\Tests\app\external\Application;

use ExternalSignatoryBook\Application\DocumentLink;
use ExternalSignatoryBook\Domain\Exceptions\ParameterCanNotBeEmptyException;
use ExternalSignatoryBook\Domain\Exceptions\ParameterMustBeGreaterThanZeroException;
use MaarchCourrier\Tests\CourrierTestCase;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\AttachmentRepositorySpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\HistoryRepositorySpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\ResourceRepositorySpy;
use MaarchCourrier\Tests\app\external\signatoryBook\Mock\UserRepositoryMock;

class DocumentLinkTest extends CourrierTestCase
{
    private ResourceRepositorySpy $resourceRepositorySpy;
    private AttachmentRepositorySpy $attachmentRepositorySpy;
    private UserRepositoryMock $userRepositoryMock;
    private HistoryRepositorySpy $historyRepositorySpy;
    private DocumentLink $documentLink;

    protected function setUp(): void
    {
        $this->resourceRepositorySpy = new ResourceRepositorySpy();
        $this->attachmentRepositorySpy = new AttachmentRepositorySpy();
        $this->userRepositoryMock = new UserRepositoryMock();
        $this->historyRepositorySpy = new HistoryRepositorySpy();

        $this->documentLink = new DocumentLink(
            $this->userRepositoryMock,
            $this->resourceRepositorySpy,
            $this->attachmentRepositorySpy,
            $this->historyRepositorySpy
        );
    }

    public function testCannotRemoveDocumentLinkBecauseDocumentItemResId0()
    {
        $this->expectExceptionObject(new ParameterMustBeGreaterThanZeroException('docItemResId'));

        $this->documentLink->removeExternalLink(0, '', '', '');
    }

    public function testCannotRemoveDocumentLinkBecauseDocumentTitleIsEmpty()
    {
        $this->expectExceptionObject(new ParameterCanNotBeEmptyException('docItemTitle'));

        $this->documentLink->removeExternalLink(1, '', '', '');
    }

    public function testCannotRemoveDocumentLinkBecauseDocumentTypeIsEmpty()
    {
        $this->expectExceptionObject(new ParameterCanNotBeEmptyException('type'));

        $this->documentLink->removeExternalLink(1, 'Document from external parapheur', '', '');
    }

    public function testCannotRemoveDocumentLinkBecauseDocumentTypeIsNotResourceOrAttachment()
    {
        $this->expectExceptionObject(new ParameterCanNotBeEmptyException('type', implode(' or ', ['resource', 'attachment'])));

        $this->documentLink->removeExternalLink(1, 'Document from external parapheur', 'test', '');
    }

    public function testCannotRemoveDocumentLinkBecauseDocumentItemExternalIdIsEmpty()
    {
        $this->expectExceptionObject(new ParameterCanNotBeEmptyException('docItemExternalId'));

        $this->documentLink->removeExternalLink(1, 'Document from external parapheur', 'resource', '');
    }

    public function testRemoveResourceDocumentLinkSuccessfully()
    {
        $this->documentLink->removeExternalLink(1, 'Document from external parapheur', 'resource', '1234');
        $this->assertTrue($this->resourceRepositorySpy->externalIdRemoved);
    }

    public function testRemoveAttachmentDocumentLinkSuccessfully()
    {
        $this->documentLink->removeExternalLink(1, 'Document from external parapheur', 'attachment', '1234');
        $this->assertTrue($this->attachmentRepositorySpy->externalIdRemoved);
    }

    public function testAddHistoryToResourceSuccessfully()
    {
        $this->documentLink->removeExternalLink(1, 'Document from external parapheur', 'attachment', '1234');
        $this->assertTrue($this->historyRepositorySpy->historyAdded);
    }
}
