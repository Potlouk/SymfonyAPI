<?php

namespace App\Repository;

use App\Entity\CULog;
use App\Entity\Document;
use App\Entity\Report;
use App\Entity\Template;
use App\Exception\LogicException;
use App\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CULogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CULog::class);
    }

     /**
      *
     */
    public function getLogFrom(object $entity): ?array {
        $field = $this->checkInstance($entity);
        $queryBuilder = $this->createQueryBuilder('log')
            ->select(['log.action','log.madeBy', 'log.created_at'])
            ->Where("log.{$field} = :entity")
            ->setParameter('entity', $entity)
            ->orderBy('log.id', 'DESC')
            ->getQuery();
           
        $logs = $queryBuilder->getArrayResult();

        foreach($logs as &$log){
            $formattedDate = $log['created_at']->format('Y-m-d H:i:s');
            $log['createdAt'] = $formattedDate;
            unset($log['created_at']);
        }
        
        return $logs;        
    }

    
     /**
     * @throws ResourceNotFoundException
     */
    private function checkInstance(object $attachment): string {
        if ($attachment instanceof Document)
            return "document";
        if ($attachment instanceof Report)
            return "report";
        if ($attachment instanceof Template)
            return "template";
           
        throw new LogicException('Unknown instance passed');
    }


}
