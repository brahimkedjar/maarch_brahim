<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Delete Group In Signatory Book Test
 * @author dev@maarch.org
 */

namespace Unit\SignatureBook\Application\Group;

use MaarchCourrier\Group\Domain\Group;
use MaarchCourrier\SignatureBook\Application\Group\DeleteGroupInSignatoryBook;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupDeletionInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action\SignatureServiceJsonConfigLoaderMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Group\MaarchParapheurGroupServiceMock;
use PHPUnit\Framework\TestCase;

class DeleteGroupInSignatoryBookTest extends TestCase
{
    private MaarchParapheurGroupServiceMock $maarchParapheurGroupServiceMock;
    private DeleteGroupInSignatoryBook $deleteGroupInSignatoryBook;
    private SignatureServiceJsonConfigLoaderMock $signatureServiceJsonConfigLoaderMock;

    protected function setUp(): void
    {
        $this->maarchParapheurGroupServiceMock = new MaarchParapheurGroupServiceMock();
        $this->signatureServiceJsonConfigLoaderMock = new SignatureServiceJsonConfigLoaderMock();
        $this->deleteGroupInSignatoryBook = new DeleteGroupInSignatoryBook(
            $this->maarchParapheurGroupServiceMock,
            $this->signatureServiceJsonConfigLoaderMock
        );
    }

    /**
     * @return void
     * @throws GroupDeletionInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testDeletesGroupSuccessfullyInSignatoryBook(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->groupIsDeleted = true;
        $deletedGroup = $this->deleteGroupInSignatoryBook->deleteGroup($group);

        $this->assertTrue($deletedGroup);
        $this->assertTrue($this->maarchParapheurGroupServiceMock->groupIsDeletedCalled);
    }

    /**
     * @return void
     * @throws GroupDeletionInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testThrowsErrorWhenGroupDeletionFailsInSignatoryBook(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->groupIsDeleted =
            ['errors' => 'Error occurred during the deletion of the Maarch Parapheur group.'];
        $this->expectException(GroupDeletionInSignatureBookFailedProblem::class);

        $this->deleteGroupInSignatoryBook->deleteGroup($group);
    }

    /**
     * @return void
     * @throws GroupDeletionInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testThrowsProblemWhenSignatureBookConfigNotFound(): void
    {
        $this->signatureServiceJsonConfigLoaderMock->signatureServiceConfigLoader = null;
        $externalId['internalParapheur'] = 5;

        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->expectException(SignatureBookNoConfigFoundProblem::class);
        $this->deleteGroupInSignatoryBook->deleteGroup($group);
    }
}
