<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve User Stamps Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Controller;

use MaarchCourrier\SignatureBook\Application\Stamp\RetrieveUserStamps;
use MaarchCourrier\SignatureBook\Domain\Problem\CannotAccessOtherUsersSignaturesProblem;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\SignatureRepository;
use MaarchCourrier\Core\Domain\User\Problem\UserDoesNotExistProblem;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class RetrieveUserStampsController
{
    /**
     * @throws CannotAccessOtherUsersSignaturesProblem
     * @throws UserDoesNotExistProblem
     */
    public function getUserSignatureStamps(Request $request, Response $response, array $args): Response
    {
        $userRepository = new UserRepository();
        $signatureServiceRepository = new SignatureRepository();
        $currentUserInformations = new CurrentUserInformations();

        $retrieveUserStamps = new RetrieveUserStamps(
            $userRepository,
            $signatureServiceRepository,
            $currentUserInformations
        );
        return $response->withJson($retrieveUserStamps->getUserSignatures($args['id']));
    }
}
