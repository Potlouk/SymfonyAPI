<?php

namespace App\Repository;

use App\Entity\Document;
use App\Exception\ResourceNotFoundException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;


class DocumentRepository extends ServiceEntityRepository
{
    private CULogRepository $logRepository;

    public function __construct(ManagerRegistry $registry, CULogRepository $logRepository)
    {
        parent::__construct($registry, Document::class);
        $this->logRepository = $logRepository;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $access
     * @return array<string, mixed>
     * @throws Exception
     */
    public function paginate(array $filters, int $page, int $limit, array $access): array
    {
        if (empty($access)) return ['data' => [], 'lastPage' => 0, 'total' => 0];

        $qb = $this->createQueryBuilder('document');

        if ($access !== ['*'])
            $qb->andWhere('document.uuid IN (:access)')
                ->setParameter('access', $access);

        if (array_key_exists('name', $filters)) {
            if (null !== $filters['name'] && "" !== $filters['name'])
                $qb->andWhere("LOWER(JSON_GET_FIELD_AS_TEXT(document.info, 'name')) LIKE :search")
                    ->setParameter('search', '%' . strtolower($filters['name']) . '%');
        }

        if (array_key_exists('type', $filters)) {
            if (!empty($filters['type']))
                $qb->andWhere('document.type = :documentType')
                    ->setParameter('documentType', $filters['type']);
        }

        if (array_key_exists('dates', $filters)) {
            if (!empty($filters['dates']))
                $qb->andWhere('document.created_at >= :validDate')
                    ->andWhere('document.created_at <= :expiryDate')
                    ->setParameter('validDate', new DateTime($filters['dates']['validDate']))
                    ->setParameter('expiryDate', new DateTime($filters['dates']['expiryDate']));
        }

        if (array_key_exists('assessmentType', $filters)) {
            if (!empty($filters['assessmentType']))
                $qb->andWhere("JSON_GET_FIELD_AS_TEXT(JSON_GET_FIELD(document.info, 'assessmentType'),'id') IN (:assessmentTypes)")
                    ->setParameter('assessmentTypes', $filters['assessmentType']);
        }

        if (array_key_exists('propertyIds', $filters)) {
            if (!empty($filters['propertyIds']))
                $qb->innerJoin('document.property', 'property')
                    ->andWhere('property.id IN (:propertyIds)')
                    ->setParameter('propertyIds', $filters['propertyIds']);
        }

        if (array_key_exists('labelIds', $filters)) {
            if (!empty($filters['labelIds']))
                $qb->innerJoin('document.labels', 'label')
                    ->andWhere('label.id IN (:labels)')
                    ->setParameter('labels', $filters['labelIds']);
        }


        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT document.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $result = $qb->orderBy('document.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($result as $document) {
            $documentData = $document;
            $documentData->logs = $this->logRepository->getLogFrom($document);
            $documentsWithLogs[] = $documentData;
        }

        $lastPage = (int)ceil($total / $limit);

        return [
            'data' => $documentsWithLogs ?? [],
            'lastPage' => $lastPage,
            'total' => $total,
        ];
    }

    /**
     * @throws ResourceNotFoundException
     * @returns Document
     */
    public function findByUuid(string $uuid): Document
    {
        $document = $this->findOneBy(['uuid' => $uuid]);
        if (null === $document)
            throw new ResourceNotFoundException('document');

        return $document;
    }

    /**
     * @param int $id of property.
     * @return array
     */
    public function countAssessmentSubTypes(int $id): array
    {
        $qb = $this->createQueryBuilder('document')
            ->select("JSON_GET_FIELD_AS_TEXT(JSON_GET_FIELD(document.info, 'assessmentType'), 'name') as name, COUNT(document.id) as count")
            ->where('document.type = :assessment')
            ->setParameter('assessment', 'assessment')
            ->innerJoin('document.property', 'property')
            ->andWhere('property.id = :propertyId')
            ->setParameter('propertyId', $id)
            ->groupBy('name');

        return $qb->getQuery()->getResult();
    }
}
