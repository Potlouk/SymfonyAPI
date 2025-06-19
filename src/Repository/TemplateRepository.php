<?php

namespace App\Repository;

use App\Entity\Template;
use App\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Template>
 */
class TemplateRepository extends ServiceEntityRepository
{
    private CULogRepository $logRepository;

    public function __construct(ManagerRegistry $registry, CULogRepository $logRepository)
    {
        parent::__construct($registry, Template::class);
        $this->logRepository = $logRepository;
    }

    /**
     * @return array<string, mixed>
     */
    public function paginate(int $page, int $limit, string $search): array { 
    
        $qb = $this->createQueryBuilder('template');
         
        if ($search !== "null" && $search !== "") {
            $qb->andWhere("template.name LIKE :search")
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(template.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $result = $qb->orderBy('template.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($result as $template) {
            $templateData = $template;
            $templateData->logs = $this->logRepository->getLogFrom($template);
            $templatesWithLogs[] = $templateData;
        }
        
        $lastPage = (int) ceil($total / $limit);
        
        return [
            'data' => $templatesWithLogs ?? [],
            'lastPage' => $lastPage,
            'total' => $total,
        ];
    }

    /**
     * @throws ResourceNotFoundException if not found.
     */
    public function findByUuid(string $uuid): Template {
        $template = $this->findOneBy(['uuid' => $uuid]);
        if (is_null($template))
        throw new ResourceNotFoundException('Template');

        return $template;
    }
}
