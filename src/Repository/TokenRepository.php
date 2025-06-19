<?php

namespace App\Repository;

use App\Entity\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }
    
    /**
     * @throws AccessDeniedException
     */
    public function findByValue(string $value): Token {
        $token = $this->findOneBy(['value' => $value]);
        if (null === $token)
        throw new AccessDeniedException('Unknow token passed');

        return $token;
    }
}
