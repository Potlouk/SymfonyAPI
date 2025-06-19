<?php

namespace App\Repository;

use App\Entity\Report;
use App\Exception\ResourceNotFoundException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;


/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    private CULogRepository $logRepository;

    public function __construct(ManagerRegistry $registry, CULogRepository $logRepository)
    {
        parent::__construct($registry, Report::class);
        $this->logRepository = $logRepository;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $access
     * @return array<string, mixed>
     * @throws Exception
     */
    public function paginate(array $filters, int $limit, int $page, array $access): array {
        if (empty($access)) return ['data' => [],'lastPage' => 0, 'total' => 0];
        
        $qb = $this->createQueryBuilder('report');
        
         if ($access !== ['*'])
         $qb->andWhere('report.uuid IN (:access)')
             ->setParameter('access', $access);

       if (array_key_exists('name', $filters)) {
        if (null !== $filters['name'])
            $qb->andWhere("LOWER(JSON_GET_FIELD_AS_TEXT(report.info, 'name')) LIKE :search")
                ->setParameter('search', '%' . strtolower($filters['name']) . '%');
        }

        if (array_key_exists('type', $filters)) {
            if(null !== $filters['type'])
            $qb->andWhere('report.type = :reportType')
                ->setParameter('reportType', $filters['type']);
        }

        if (array_key_exists('dates', $filters)) {
            if(!empty($filters['dates']))
            $qb->andWhere('report.created_at >= :startDate')
            ->andWhere('report.created_at <= :endDate')
            ->setParameter('startDate', new DateTime($filters['dates']['startDate']))
            ->setParameter('endDate', new DateTime($filters['dates']['endDate']));
        }

        if (array_key_exists('assessmentType', $filters)) {
            if(!empty($filters['assessmentType']))
            $qb->andWhere("JSON_GET_FIELD_AS_TEXT(report.info.assessmentType, 'id') IN (:assessmentTypes)")
               ->setParameter('assessmentTypes', $filters['assessmentType']);
        }

        if (array_key_exists('propertyIds', $filters)) {
            if(!empty($filters['propertyIds']))
            $qb->innerJoin('report.property', 'property') 
                ->andWhere('property.id IN (:propertyIds)')
                ->setParameter('propertyIds', $filters['propertyIds']); 
        }

        if (array_key_exists('labelIds', $filters)) {
            if(!empty($filters['labelIds']))
            $qb->innerJoin('report.labels', 'label')
                ->andWhere('label.id IN (:labels)') 
                ->setParameter('labels', $filters['labelIds']);
        }
        
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT report.id)')
            ->getQuery()
            ->getSingleScalarResult();
 
         $result = $qb->orderBy('report.id', 'DESC')
             ->setFirstResult(($page - 1) * $limit)
             ->setMaxResults($limit)
             ->getQuery()
             ->getResult();
 
             
             $reportsWithLogs = [];
             foreach ($result as $report) {
                 $reportData = $report; 
                 $reportData->logs = $this->logRepository->getLogFrom($report);
                 $reportsWithLogs[] = $reportData;
             }

         $lastPage = (int) ceil($total / $limit);
 
         return [
             'data' => $reportsWithLogs,
             'lastPage' => $lastPage,
             'total' => $total,
         ];
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function findByUuid(string $uuid): Report {
        $report = $this->findOneBy(['uuid' => $uuid]);
        if (null === $report)
        throw new ResourceNotFoundException('Report');
    
        return $report;
    }

     /**
     * @throws ResourceNotFoundException
     */
    public function findByDocumentId(int $id): Report {
        $qb = $this->createQueryBuilder('report')
            ->innerJoin('report.document', 'document')
            ->andWhere('document.id = :id')
            ->setParameter('id', $id);

        $report = $qb->getQuery()->getOneOrNullResult();

        if (null === $report) {
            throw new ResourceNotFoundException('Report');
        }

        return $report;
    }
}
