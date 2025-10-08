<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief CreateAndUpdateUserInSignatoryBookTest
 * @author dev@maarch.org
 */

namespace Unit\SignatureBook\Application\User;

use MaarchCourrier\SignatureBook\Application\User\CreateAndUpdateUserInSignatoryBook;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserCreateInSignatureBookFailedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\UserUpdateInSignatureBookFailedProblem;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\Action\SignatureServiceJsonConfigLoaderMock;
use MaarchCourrier\User\Domain\User;
use PHPUnit\Framework\TestCase;
use MaarchCourrier\Tests\Unit\SignatureBook\Mock\User\MaarchParapheurUserServiceMock;

class CreateAndUpdateUserInSignatoryBookTest extends TestCase
{
    private MaarchParapheurUserServiceMock $signatureBookUserServiceMock;
    private CreateAndUpdateUserInSignatoryBook $createAndUpdateUserInSignatoryBook;
    private SignatureServiceJsonConfigLoaderMock $signatureServiceJsonConfigLoaderMock;

    protected function setUp(): void
    {
        $this->signatureBookUserServiceMock = new MaarchParapheurUserServiceMock();
        $this->signatureServiceJsonConfigLoaderMock = new SignatureServiceJsonConfigLoaderMock();
        $this->createAndUpdateUserInSignatoryBook = new CreateAndUpdateUserInSignatoryBook(
            $this->signatureBookUserServiceMock,
            $this->signatureServiceJsonConfigLoaderMock,
        );
    }

    /**
     * @return void
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserCreateInSignatureBookFailedProblem
     * @throws UserUpdateInSignatureBookFailedProblem
     */
    public function testTheUserHasNoExternalIdSoAnAccountIsCreated(): void
    {
        $dataExpected['internalParapheur'] = 12;
        $ExpectedUser = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId($dataExpected);

        $user = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId([]);


        $newUser = $this->createAndUpdateUserInSignatoryBook->createAndUpdateUser($user);

        $this->assertTrue($this->signatureBookUserServiceMock->createUserCalled);
        $this->assertEquals($ExpectedUser, $newUser);
    }

    /**
     * @return void
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserCreateInSignatureBookFailedProblem
     * @throws UserUpdateInSignatureBookFailedProblem
     */
    public function testCreatesAccountWhenNoExternalIdAndThrowsProblemOnFailure(): void
    {
        $user = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId([]);
        $this->signatureBookUserServiceMock->userCreated =
            ['errors' => 'Error occurred during the creation of the Maarch Parapheur user.'];
        $this->expectException(UserCreateInSignatureBookFailedProblem::class);
        $this->createAndUpdateUserInSignatoryBook->createAndUpdateUser($user);
    }

    /**
     * @return void
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserCreateInSignatureBookFailedProblem
     * @throws UserUpdateInSignatureBookFailedProblem
     */
    public function testUpdatesUserWhenExternalIdExistsInSignatoryBook(): void
    {
        $dataExpected['maarchParapheur'] = 10;
        $dataExpected['internalParapheur'] = 12;

        $ExpectedUser = (new User())
            ->setFirstname('firstname2')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId($dataExpected);

        $actualData['maarchParapheur'] = 10;
        $actualData['internalParapheur'] = 12;

        $user = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId($actualData);

        $this->signatureBookUserServiceMock->userExists = true;
        $newUser = $this->createAndUpdateUserInSignatoryBook->createAndUpdateUser($user);
        $this->assertTrue($this->signatureBookUserServiceMock->updateUserCalled);
        $this->assertEquals($ExpectedUser, $newUser);
    }

    /**
     * @return void
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserCreateInSignatureBookFailedProblem
     * @throws UserUpdateInSignatureBookFailedProblem
     */
    public function testUpdatesUserWhenExternalIdExistsInSignatoryBookAndThrowsProblemOnFailure(): void
    {
        $actualData['maarchParapheur'] = 10;
        $actualData['internalParapheur'] = 12;

        $user = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId($actualData);
        $this->signatureBookUserServiceMock->userExists = true;
        $this->signatureBookUserServiceMock->userUpdated =
            ['errors' => 'Failed to update the user in Maarch Parapheur.'];
        $this->expectException(UserUpdateInSignatureBookFailedProblem::class);
        $this->createAndUpdateUserInSignatoryBook->createAndUpdateUser($user);
    }

    /**
     * @return void
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserCreateInSignatureBookFailedProblem
     * @throws UserUpdateInSignatureBookFailedProblem
     */
    public function testCreatesAccountWhenExternalIdNotFoundInSignatoryBook(): void
    {
        $dataExpected['maarchParapheur'] = 10;
        $dataExpected['internalParapheur'] = 12;

        $ExpectedUser = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId($dataExpected);

        $actualData['maarchParapheur'] = 10;

        $user = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId($actualData);
        $this->signatureBookUserServiceMock->userExists = false;
        $newUser = $this->createAndUpdateUserInSignatoryBook->createAndUpdateUser($user);
        $this->assertTrue($this->signatureBookUserServiceMock->createUserCalled);
        $this->assertEquals($ExpectedUser, $newUser);
    }

    /**
     * @return void
     * @throws SignatureBookNoConfigFoundProblem
     * @throws UserCreateInSignatureBookFailedProblem
     * @throws UserUpdateInSignatureBookFailedProblem
     */
    public function testCreatesAccountWhenExternalIdNotFoundAndThrowsProblemOnFailure(): void
    {
        $actualData['maarchParapheur'] = 10;

        $user = (new User())
            ->setFirstname('firstname')
            ->setLastname('lastname')
            ->setMail('mail')
            ->setLogin('userId')
            ->setExternalId($actualData);
        $this->signatureBookUserServiceMock->userExists = false;
        $this->signatureBookUserServiceMock->userCreated =
            ['errors' => 'Error occurred during the creation of the Maarch Parapheur user.'];
        $this->expectException(UserCreateInSignatureBookFailedProblem::class);
        $this->createAndUpdateUserInSignatoryBook->createAndUpdateUser($user);
    }
}
