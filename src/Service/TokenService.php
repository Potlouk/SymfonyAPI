<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Permission;
use App\Entity\Report;
use App\Entity\Token;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;

readonly class TokenService {

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Updates token settings for a document or report
     *
     * Modifies token permissions, expiration date, or active status
     * based on the provided data.
     *
     * @param Document|Report $parent The entity owning the token
     * @param array<string, mixed> $data Token settings to update
     * @return Token The updated token entity
     * @throws EntityNotFoundException When token is not found
     * @throws Exception
     */
    public function patch(Document|Report $parent, array $data): Token {
        $token = $parent->getToken();

        if (null === $token)
        throw new EntityNotFoundException('token');
    
        if (array_key_exists('permissions', $data) && isset($data['permissions']))
        $token->setPermissions(
            (new Permission())->setValue($data['permissions'])
        );

        $token->setActive(
            $data['active'] ?? $token->isActive()
        );

        $token->setExpiryDate(
            (null === $data['expDate']) ? null : new DateTimeImmutable($data['expDate'])
        );

        $this->entityManager->flush();
        return $token;
    }

}