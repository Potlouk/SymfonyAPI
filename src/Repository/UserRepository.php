<?php

namespace App\Repository;

use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): User
    {
        $user = $this->findOneBy(['email' => $email]);
        if (null === $user)
            throw new ResourceNotFoundException('User');

        return $user;
    }

    public function findById(int $id): User
    {
        $user = $this->findOneBy(['id' => $id]);
        if (null === $user)
            throw new ResourceNotFoundException('User');

        return $user;
    }

    public function countAdmins(): int
    {
        $qb = $this->createQueryBuilder('user');
        $qb->select('COUNT(user.id)')
            ->where('user.role = :roleId')
            ->setParameter('roleId', 1);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}