<?php

namespace App\Service;

use App\Exception\ResourceNotFoundException;
use App\Factory\PropertyStatistics;
use App\Repository\DocumentRepository;
use App\Repository\PropertyRepository;

final readonly class StatsService {

    public function __construct(
        private PropertyRepository $propertyRepository,
        private DocumentRepository $documentRepo,
    ) {}

    /**
     * Generates statistics for a property
     * 
     * Creates a PropertyStatistics object and builds statistical data
     * based on the provided filters.
     * 
     * @param int $id Property ID
     * @param array<string, mixed> $filters Filtering parameters for statistics
     * @return array<string, mixed> Statistical data for the property
     * @throws ResourceNotFoundException When property cannot be found
     */
    public function property(int $id, array $filters): array {
        $property = $this->propertyRepository->findById($id);

        return (new PropertyStatistics(
                $property, 
                $this->documentRepo,
                $this->propertyRepository
        ))->buildFrom($filters);
    }
}