<?php

namespace App\Repository;

use App\Entity\Label;
use App\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Label>
 */
class LabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Label::class);
    }


    /**
     * @throws ResourceNotFoundException
     */
    public function findById(int $id): object {
        $property = $this->findOneBy(['id' => $id]);
        if (null === $property)
        throw new ResourceNotFoundException('Label');

        return $property;
    }
}
