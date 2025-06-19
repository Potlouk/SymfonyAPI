<?php

namespace App\Repository;

use App\Entity\Property;
use App\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function paginate(int $page, int $limit, string $archived, string $search): array { 
        $archivedBool = filter_var($archived, FILTER_VALIDATE_BOOLEAN);
        $qb = $this->createQueryBuilder('property')
            ->where('property.archived = :archived')
            ->setParameter('archived', $archivedBool);
    
        if ($search !== "null" && $search !== "")
            $qb->andWhere("LOWER(JSON_GET_FIELD_AS_TEXT(property.data, 'name')) LIKE :search")
                ->setParameter('search', '%' . strtolower($search) . '%');


        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(property.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $result = $qb->orderBy('property.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($result as $property)
        $property->countDocuments = $this->countDocuments($property->getId());
        
        $lastPage = (int) ceil($total / $limit);

        return [
            'data' => $result,
            'lastPage' => $lastPage,
            'total' => $total,
        ];
    }
    
    /**
     * @throws ResourceNotFoundException
     */
    public function findById(int $id): Property {
        $property = $this->findOneBy(['id' => $id]);
        if (null === $property)
        throw new ResourceNotFoundException('Property');

        return $property;
    }


    private function countDocuments(int $id) : int{
        $result = $this->createQueryBuilder('property')
        ->innerJoin('property.documents', 'document')
        ->select("COUNT(document.id) as count")
            ->where('property.id = :id')
            ->setParameter('id', $id);
    
        return (int) $result->getQuery()->getSingleScalarResult();
    }


    public function lastSubmittedDocument(int $id, string $type): ?string {
        $result = $this->createQueryBuilder('property')
            ->select('document.uuid')
            ->innerJoin('property.documents', 'document')
                ->where('property.id = :id')
                ->andWhere('document.type = :type')
                ->andWhere('document.status = :status')
                ->setParameter('id', $id)
                ->setParameter('type', $type)
                ->setParameter('status', 'submitted')
                ->orderBy('document.created_at', 'DESC')
                ->setMaxResults(1);

        $document = $result->getQuery()->getOneOrNullResult();
        return $document ? $document['uuid'] : null;
    }

    /**
     * @param int $id
     * @param string $type
     * @param string $status
     * @return int
     */
    public function countDocumentsBoth(int $id, string $type, string $status): int {
    
        $result = $this->createQueryBuilder('property')
        ->innerJoin('property.documents', 'document')
        ->select("COUNT(document.id) as count")
            ->where('property.id = :id')
            ->andWhere('document.type = :type')
            ->andWhere('document.status = :status')
            ->setParameter('id', $id)
            ->setParameter('type', $type)
            ->setParameter('status', $status);
    
        return (int) $result->getQuery()->getSingleScalarResult();
    }

}
