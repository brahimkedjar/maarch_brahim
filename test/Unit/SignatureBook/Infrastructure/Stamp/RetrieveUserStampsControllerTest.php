<?php

namespace MaarchCourrier\Tests\Unit\SignatureBook\Infrastructure\Stamp;

use MaarchCourrier\SignatureBook\Infrastructure\Controller\RetrieveUserStampsController;
use MaarchCourrier\Tests\CourrierTestCase;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use SrcCore\http\Response;
use User\controllers\UserController;

class RetrieveUserStampsControllerTest extends CourrierTestCase
{
    private ?string $previousConnectedUser;

    protected function setUp(): void
    {
        $currentUser = new CurrentUserInformations();
        if ($currentUser->getCurrentUserId() !== null) {
            $this->previousConnectedUser = $GLOBALS['login'];
        }
    }

    protected function tearDown(): void
    {
        if ($this->previousConnectedUser !== null) {
            $this->connectAsUser($this->previousConnectedUser);
        }
    }

    private function addUserSignature(int $userId): void
    {
        $userController = new UserController();
        $body = [
            'base64' => base64_encode(file_get_contents("install/samples/templates/2021/03/0001/0009_1477994073.jpg")),
            'label' => "signature-icon.jpg",
            'name' => "signature-icon.jpg",
        ];
        $fullRequest = $this->createRequestWithBody('POST', $body);
        $userController->addSignature($fullRequest, new Response(), ['id' => $userId]);
    }

    public function testGetAListOfUserStampsFromApiRouteExpectNoExceptions(): void
    {
        $this->connectAsUser('bbain');
        $userId = $GLOBALS['id'];
        $this->addUserSignature($userId);
        $retrieveUserStampsController = new RetrieveUserStampsController();
        $request = $this->createRequest('GET');

        $response = $retrieveUserStampsController->getUserSignatureStamps($request, new Response(), ['id' => $userId]);
        $userStamps = json_decode((string)$response->getBody(), true);

        $this->assertNotEmpty($userStamps);
        $this->assertIsArray($userStamps);
        $this->assertNotEmpty($userStamps[0]);
        $this->assertIsArray($userStamps[0]);
    }
}
