<?php

namespace App\Service;

use App\Constraint\RequestConstraints;
use App\Entity\Property;
use App\Exception\ResourceNotFoundException;
use App\Factory\PropertyFactory;
use App\Repository\DocumentRepository;
use App\Repository\PropertyRepository;
use App\Trait\ServiceHelper;
use Doctrine\ORM\EntityManagerInterface;

final class PropertyService {
use ServiceHelper;
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PropertyRepository     $repository,
        private readonly DocumentRepository     $documentRepository,
        private readonly ImageService           $imageService,
    ) {}

    /**
     * Paginates properties with filtering options
     * 
     * Retrieves a paginated list of properties with options for archived status
     * and text search.
     * 
     * @param int $page The page number to retrieve
     * @param int $limit Maximum number of items per page
     * @param string $archived Filter for archived properties ('true', 'false', 'all')
     * @param string $search Text to search for in property fields
     * @return array<string, mixed> Paginated results with metadata
     */
    public function paginate(int $page, int $limit, string $archived, string $search): array{
        return $this->repository->paginate($page, $limit, $archived, $search);
    }

    /**
     * Retrieves a property by ID
     * 
     * @param int $id Property ID
     * @return Property The requested property entity
     * @throws ResourceNotFoundException When property cannot be found
     */
    public function get(int $id): Property {
        return $this->repository->findById($id);
    }

    /**
     * Creates a new property
     * 
     * Validates the input data and creates a new property using the PropertyFactory.
     * 
     * @param array<string, mixed> $data Property creation parameters
     * @return Property The newly created property entity
     */
    public function create(array $data): Property {
        $this->validateRequestData($data, RequestConstraints::propertyConstraintCreate());

        $property = PropertyFactory::build($data);
        $this->entityManager->persist($property);
        $this->entityManager->flush();

        return $property;
    }

    /**
     * Permanently deletes a property
     * 
     * Removes the property from the database.
     * 
     * @param int $id Property ID
     * @return void
     */
    public function delete(int $id): void {
        $property = $this->get($id);

        if (null !== $property->getLogo())
        $this->imageService->deleteFolder($property->getId());

        foreach ($property->getDocuments() as $document)
            $this->imageService->deleteFolder($document->getUuid());

        foreach ($property->getReports() as $report)
            $this->imageService->deleteFolder($report->getUuid());

        $this->entityManager->remove($property);
        $this->entityManager->flush();
    }

    /**
     * Updates specific fields of a property
     * 
     * Validates the input data and updates the property fields.
     * 
     * @param int $id Property ID
     * @param array<string, mixed> $data Fields to update
     * @return Property The updated property entity
     */
    public function patch(int $id, array $data): Property {
        $this->validateRequestData($data, RequestConstraints::propertyConstraintPatch());
        $property = $this->get($id);

        $property->setArchived($data['archived'] ?? $property->isArchived());
        $property->setData($data['data'] ?? $property->getData());

        if (!($data['data']['logo'] ?? false) && null !== $property->getLogo())
        $this->imageService->delete(['paths' => [ $property->getLogo() ]]);

        $this->entityManager->flush();
        return $property;
    }
}