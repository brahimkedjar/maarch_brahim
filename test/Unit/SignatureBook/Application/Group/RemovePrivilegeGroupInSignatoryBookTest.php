<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Remove Privilege Group In Signatory Book Test
 * @author dev@maarch.org
 */

namespace Unit\SignatureBook\Application\Group;

use MaarchCourrier\Group\Domain\Group;
use MaarchCourrier\SignatureBook\Application\Group\RemovePrivilegeGroupInSignatoryBook;
use MaarchCourrier\SignatureBook\Domain\Privilege\SignDocumentPrivilege;
use MaarchCourrier\SignatureBook\Domain\Privilege\VisaDocumentPrivilege;
use MaarchCourrier\SignatureBook\Domain\Problem\GetSignatureBookGroupPrivilegesFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\GroupUpdatePrivilegeInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\Tests\Unit\Authorization\Mock\PrivilegeCheckerMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action\SignatureServiceJsonConfigLoaderMock;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Group\MaarchParapheurGroupServiceMock;
use PHPUnit\Framework\TestCase;

class RemovePrivilegeGroupInSignatoryBookTest extends TestCase
{
    private MaarchParapheurGroupServiceMock $maarchParapheurGroupServiceMock;
    private RemovePrivilegeGroupInSignatoryBook $removePrivilegeGroupInSignatoryBook;
    private SignatureServiceJsonConfigLoaderMock $signatureServiceJsonConfigLoaderMock;
    private PrivilegeCheckerMock $privilegeCheckerMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->maarchParapheurGroupServiceMock = new MaarchParapheurGroupServiceMock();
        $this->signatureServiceJsonConfigLoaderMock = new SignatureServiceJsonConfigLoaderMock();
        $this->privilegeCheckerMock = new PrivilegeCheckerMock();
        $this->removePrivilegeGroupInSignatoryBook = new RemovePrivilegeGroupInSignatoryBook(
            $this->maarchParapheurGroupServiceMock,
            $this->signatureServiceJsonConfigLoaderMock,
            $this->privilegeCheckerMock,
        );
    }

    /**
     * @return void
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testDoesNotUpdatePrivilegesWhenOneIsDeactivatedButAnotherIsStillActive(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->isPrivilegeRetrieveFailed = false;
        $this->maarchParapheurGroupServiceMock->privilegeIsChecked = false;
        $this->maarchParapheurGroupServiceMock->checked = true;
        $this->privilegeCheckerMock->hasGroupPrivilege = true;
        $this->maarchParapheurGroupServiceMock->privilege = ['indexation', 'manage_documents'];
        $this->removePrivilegeGroupInSignatoryBook->removePrivilege($group, new SignDocumentPrivilege());
        $this->assertFalse($this->maarchParapheurGroupServiceMock->groupUpdatePrivilegeCalled);
        $this->assertTrue($this->privilegeCheckerMock->hasGroupPrivilegeCalled);
    }

    /**
     * @return void
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testUpdatesPrivilegesWhenSinglePrivilegeIsDeactivated(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->isPrivilegeRetrieveFailed = false;
        $this->maarchParapheurGroupServiceMock->privilegeIsChecked = false;
        $this->maarchParapheurGroupServiceMock->privilege = ['indexation', 'manage_documents'];
        $this->maarchParapheurGroupServiceMock->checked = true;
        $this->privilegeCheckerMock->hasGroupPrivilege = false;

        $this->removePrivilegeGroupInSignatoryBook->removePrivilege($group, new VisaDocumentPrivilege());

        $this->assertTrue($this->maarchParapheurGroupServiceMock->groupUpdatePrivilegeCalled);
    }

    /**
     * @return void
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testThrowsProblemWhenDeactivatingOnlyPrivilegeAndUpdateFails(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->isPrivilegeRetrieveFailed = false;
        $this->maarchParapheurGroupServiceMock->privilegeIsChecked = false;
        $this->maarchParapheurGroupServiceMock->privilege = ['indexation', 'manage_documents'];
        $this->maarchParapheurGroupServiceMock->checked = true;
        $this->privilegeCheckerMock->hasGroupPrivilege = false;
        $this->maarchParapheurGroupServiceMock->privilegesGroupUpdated = [
            'errors' => 'Error occurred during the update of the group privilege in Maarch Parapheur.'
        ];
        $this->expectException(GroupUpdatePrivilegeInSignatureBookFailedProblem::class);
        $this->removePrivilegeGroupInSignatoryBook->removePrivilege($group, new SignDocumentPrivilege());
    }

    /**
     * @return void
     * @throws GetSignatureBookGroupPrivilegesFailedProblem
     * @throws GroupUpdatePrivilegeInSignatureBookFailedProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function testThrowsProblemWhenTheRetrieveOfTheGroupPrivilegeFailed(): void
    {
        $externalId['internalParapheur'] = 5;
        $group = (new Group())
            ->setLabel('test')
            ->setExternalId($externalId);

        $this->maarchParapheurGroupServiceMock->isPrivilegeRetrieveFailed = true;
        $this->maarchParapheurGroupServiceMock->privilege = ['indexation', 'manage_documents'];

        $this->expectException(GetSignatureBookGroupPrivilegesFailedProblem::class);
        $this->removePrivilegeGroupInSignatoryBook->removePrivilege($group, new SignDocumentPrivilege());
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
        $this->removePrivilegeGroupInSignatoryBook->removePrivilege($group, new SignDocumentPrivilege());
    }
}
