<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Add Privilege Group In Signatory Book Test
 * @author dev@maarch.org
 */

namespace Unit\SignatureBook\Application\Group;

use MaarchCourrier\Core\Domain\Group\Port\GroupInterface;
use MaarchCourrier\Group\Domain\Group;
use MaarchCourrier\SignatureBook\Application\Group\AddPrivilegeGroupInSignatoryBook;
use MaarchCourrier\SignatureBook\Domain\Privilege\SignDocumentPrivilege;
use MaarchCourrier\SignatureBook\Domain\Problem\GetSignatureBookGroupPrivilegesFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupUpdatePrivilegeInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action\SignatureServiceJsonConfigLoaderMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Group\MaarchParapheurGroupServiceMock;
use PHPUnit\Framework\TestCase;

class AddPrivilegeGroupInSignatoryBookTest extends TestCase
{
    private MaarchParapheurGroupServiceMock $maarchParapheurGroupServiceMock;
    private AddPrivilegeGroupInSignatoryBook $addPrivilegeGroupInSignatoryBook;
    private SignatureServiceJsonConfigLoaderMock $signatureServiceJsonConfigLoaderMock;

    protected function setUp(): void
    {
        $this->maarchParapheurGroupServiceMock = new MaarchParapheurGroupServiceMock();
        $this->signatureServiceJsonConfigLoaderMock = new SignatureServiceJsonConfigLoaderMock();
        $this->addPrivilegeGroupInSignatoryBook = new AddPrivilegeGroupInSignatoryBook(
            $this->maarchParapheurGroupServiceMock,
            $this->signatureServiceJsonConfigLoaderMock
        );
    }

    /**
     * @return void
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testActivatesPrivilegeWhenNotActivatedInSignatoryBook(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->isPrivilegeRetrieveFailed = false;
        $this->maarchParapheurGroupServiceMock->privilege = ['indexation'];
        $updatePrivilege = $this->addPrivilegeGroupInSignatoryBook->addPrivilege($group, new SignDocumentPrivilege());
        $this->assertInstanceOf(GroupInterface::class, $updatePrivilege);
        $this->assertTrue($this->maarchParapheurGroupServiceMock->groupUpdatePrivilegeCalled);
    }

    /**
     * @return void
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testThrowsProblemWhenActivatingPrivilegeFailsInSignatoryBook(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->isPrivilegeRetrieveFailed = false;
        $this->maarchParapheurGroupServiceMock->privilege = ['indexation'];
        $this->maarchParapheurGroupServiceMock->privilegesGroupUpdated = [
            'errors' => 'Error occurred during the update of the group privilege in Maarch Parapheur.'
        ];
        $this->expectException(GroupUpdatePrivilegeInSignatureBookFailedProblem::class);
        $this->addPrivilegeGroupInSignatoryBook->addPrivilege($group, new SignDocumentPrivilege());
    }

    /**
     * @return void
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testThrowsProblemWhenRetrievingPrivilegesGroupFailsInSignatoryBook(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->isPrivilegeRetrieveFailed = true;
        $this->expectException(GetSignatureBookGroupPrivilegesFailedProblem::class);
        $this->addPrivilegeGroupInSignatoryBook->addPrivilege($group, new SignDocumentPrivilege());
    }

    /**
     * @return void
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
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
        $this->addPrivilegeGroupInSignatoryBook->addPrivilege($group, new SignDocumentPrivilege());
    }
}
