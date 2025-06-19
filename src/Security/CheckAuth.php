<?php

namespace App\Security;

use App\Exception\LogicException;
use App\Repository\DocumentRepository;
use App\Repository\ReportRepository;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


readonly class CheckAuth {
    public function __construct(
        private ReportRepository   $reportRepository,
        private DocumentRepository $documentRepository,
        private TokenRepository    $tokenRepository,
        private UserRepository     $userRepository,
        private Security           $security,
    ){}

    public function validateUserEditOnlyItself(?int $id, string $role): void {
        if (null === $this->security->getUser())
            throw new UnauthorizedHttpException('Authorization','Unauthorized');

        $user = $this->userRepository->findByEmail($this->security->getUser()->getUserIdentifier());
        
        if (in_array($role, $user->getRoles(), true))
            return;
        
        if ($user->getId() !== $id)
            throw new UnauthorizedHttpException('Authorization','Unauthorized');
    }

    public function getUserEmailFromToken(): int {
        if (null === $this->security->getUser())
            throw new UnauthorizedHttpException('Authorization','Unauthorized');

        return $this->userRepository->findByEmail($this->security->getUser()->getUserIdentifier())->getId();
    }

    /**
     * @throws UnauthorizedHttpException
     */
    public function validate(Request $request, ?string $uuid, string $type): array {

        if (null === $uuid)
            throw new UnauthorizedHttpException('','Unauthorized');

        if (!$request->headers->has('X-Public-Token'))
            throw new UnauthorizedHttpException('','Authorization token is missing');

        $requestToken = $request->headers->get('X-Public-Token');
        $token = $this->tokenRepository->findByValue($requestToken);

        if (!$token->isActive())
            throw new UnauthorizedHttpException('','Authorization token is not active');

        $tokenParent = $this->getRepositoryBasedOnType($type)->findOneBy(['uuid' => $uuid]);

        if (null === $tokenParent)
            throw new UnauthorizedHttpException('','Authorization token is corrupted');

        if ($tokenParent->getToken()->getValue() !== $requestToken)
            throw new UnauthorizedHttpException('','Authorization token miss match');

        return [
            'email'       => $token->getEmail(),
            'permissions' => $token->getPermissions()?->getValue() ?? []
        ];
    }

    /**
     * @throws LogicException
     */
    private function getRepositoryBasedOnType(string $type): ServiceEntityRepository {
        return match ($type) {
            'Document' => $this->documentRepository,
            'Report' => $this->reportRepository,
            default => throw new LogicException('Unknown entity requested'),
        };
    }

}
