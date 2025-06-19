<?php

namespace App\Repository;

use App\Entity\Setting;
use App\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }


    public function findById(int $id): object {
        $settings = $this->findOneBy(['id' => $id]);
        if(null === $settings)
        throw new ResourceNotFoundException('Settings');

        return $settings;
    }
}
