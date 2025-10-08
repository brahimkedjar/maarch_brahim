<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Retrieve Signature Book Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Controller;

use Exception;
use MaarchCourrier\Attachment\Infrastructure\Repository\AttachmentRepository;
use MaarchCourrier\Authorization\Domain\Problem\MainResourceOutOfPerimeterProblem;
use MaarchCourrier\Authorization\Infrastructure\MainResourcePerimeterCheckerService;
use MaarchCourrier\Authorization\Infrastructure\PrivilegeChecker;
use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\DocumentConversion\Infrastructure\Repository\SignatureMainDocumentRepository;
use MaarchCourrier\DocumentConversion\Infrastructure\Service\ConvertPdfService;
use MaarchCourrier\MainResource\Infrastructure\Repository\MainResourceRepository;
use MaarchCourrier\SignatureBook\Application\RetrieveSignatureBook;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\SignatureBookRepository;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\VisaWorkflowRepository;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use MaarchCourrier\User\Infrastructure\UserFactory;
use SignatureBook\controllers\SignatureBookController;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class RetrieveSignatureBookController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws MainResourceOutOfPerimeterProblem
     * @throws ResourceDoesNotExistProblem
     * @throws Exception
     */
    public function getSignatureBook(Request $request, Response $response, array $args): Response
    {
        $env = new Environment();

        if (!$env->isNewInternalParapheurEnabled()) {
            $signatureBookController = new SignatureBookController();
            return $signatureBookController->getSignatureBook($request, $response, $args);
        }

        $retrieve = new RetrieveSignatureBook(
            new MainResourceRepository(new UserFactory()),
            new CurrentUserInformations(),
            new MainResourcePerimeterCheckerService(),
            new SignatureBookRepository(),
            new SignatureMainDocumentRepository(),
            new ConvertPdfService(),
            new AttachmentRepository(new UserFactory()),
            new PrivilegeChecker(),
            new VisaWorkflowRepository(new UserFactory())
        );
        return $response->withJson($retrieve->getSignatureBook($args['resId']));
    }
}
