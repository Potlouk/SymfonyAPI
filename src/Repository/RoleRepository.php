<?php

namespace App\Repository;

use App\Entity\Role;
use App\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findById(int $id): Role {
        $role = $this->findOneBy(['id' => $id]);
        if (null === $role)
        throw new ResourceNotFoundException('Role');

        return $role;
    }

    public function isNameExisting(string $name): bool {
        $role = $this->findOneBy(['name' => $name]);
        return (null !== $role);
    }

}
