<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief RetrieveProofFileController Controller
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Infrastructure\Controller;

use MaarchCourrier\Core\Domain\MainResource\Problem\ResourceDoesNotExistProblem;
use MaarchCourrier\SignatureBook\Application\ProofFile\RetrieveProofFile;
use MaarchCourrier\SignatureBook\Domain\Problem\DocumentIsNotSignedProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\ExternalIdNotFoundProblem;
use MaarchCourrier\SignatureBook\Domain\Problem\SignatureBookNoConfigFoundProblem;
use MaarchCourrier\SignatureBook\Infrastructure\MaarchParapheurProofService;
use MaarchCourrier\SignatureBook\Infrastructure\Repository\ResourceToSignRepository;
use MaarchCourrier\SignatureBook\Infrastructure\SignatureServiceJsonConfigLoader;
use MaarchCourrier\User\Infrastructure\CurrentUserInformations;
use Slim\Psr7\Request;
use SrcCore\http\Response;

class RetrieveProofFileController
{
    /**
     * @return RetrieveProofFile
     */
    private function retrieveProofFileConstruct(): RetrieveProofFile
    {
        $currentUserInformations = new CurrentUserInformations();
        $resourceToSignRepository = new ResourceToSignRepository();
        $maarchParapheurProofService = new MaarchParapheurProofService();
        $SignatureServiceConfigLoader = new SignatureServiceJsonConfigLoader();

        return new RetrieveProofFile(
            $currentUserInformations,
            $maarchParapheurProofService,
            $resourceToSignRepository,
            $SignatureServiceConfigLoader
        );
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  array  $args
     * @return Response
     * @throws DocumentIsNotSignedProblem
     * @throws ExternalIdNotFoundProblem
     * @throws ResourceDoesNotExistProblem
     * @throws SignatureBookNoConfigFoundProblem
     */
    public function getResourceProofFile(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();

        $retrieveProofFile = $this->retrieveProofFileConstruct();

        $result = $retrieveProofFile->execute($args['resId'], false);

        if (isset($queryParams['mode']) && $queryParams['mode'] == 'base64') {
            return $response->withJson($result);
        }
        $response->write(base64_decode($result['encodedProofDocument']));
        $response = $response->withAddedHeader(
            'Content-Disposition',
            "inline; filename=maarch_history_proof." . $result['format']
        );

        return $response->withHeader('Content-Type', 'application/zip');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  array  $args
     * @return Response
     * @throws SignatureBookNoConfigFoundProblem
     * @throws ResourceDoesNotExistProblem
     * @throws DocumentIsNotSignedProblem
     * @throws ExternalIdNotFoundProblem
     */
    public function getAttachmentProofFile(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();

        $retrieveProofFile = $this->retrieveProofFileConstruct();

        $result = $retrieveProofFile->execute($args['resId'], true);

        if (isset($queryParams['mode']) && $queryParams['mode'] == 'base64') {
            return $response->withJson($result);
        }
        $response->write(base64_decode($result['encodedProofDocument']));
        $response = $response->withAddedHeader(
            'Content-Disposition',
            "inline; filename=maarch_history_proof." . $result['format']
        );

        return $response->withHeader('Content-Type', 'application/zip');
    }
}
