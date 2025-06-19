<?php

namespace App\Factory;

use App\Entity\Property;
use App\Enum\DocumentStatusTypes;
use App\Repository\DocumentRepository;
use App\Repository\PropertyRepository;

final readonly class PropertyStatistics {

    public function __construct (
        private Property           $property,
        private DocumentRepository $documentRepo,
        private PropertyRepository $propertyRepo
    ){}

     /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function buildFrom(array $filters): array {
        return [
            'latestSubmitted' => [
                'assessment' => $this->lastSubmittedUuid('assessment'),
                'workOrder' => $this->lastSubmittedUuid('workOrder'),
            ],
            "labels" => $this->labelsCount($filters),
            "assessmentTypes" => $this->assessmentTypeCount(),
            "countDocuments" => $this->documentCount(),
        ];

    }

     /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    private function labelsCount(array $filters): array {
     
        foreach ($this->property->getDocuments()->toArray() as $document){
            foreach ($document->getLabels()->toArray() as $label) {
                if (!empty($filters) && !in_array($label->getId(), $filters, true))
                continue;
            
                if (!array_key_exists('name', $label->getData()))
                continue;

                $labelName = $label->getData()['name'];
                if (!isset($result[$labelName])) {
                    $result[$labelName] = [
                        'name' => $labelName,
                        'count' => [
                            'assessment' => 0,
                            'workOrder' => 0,
                        ],
                        'color' => $label->getData()['color']
                    ];
                }

                if ($document->getType() === 'assessment')
                $result[$labelName]['count']['assessment']++;
                else $result[$labelName]['count']['workOrder']++;
            }
        }
        return array_values($result ?? []);
    }

    private function lastSubmittedUuid(string $type): ?string {
       return $this->propertyRepo->lastSubmittedDocument($this->property->getId(),$type);
    }
    /**
     * @return array<int, mixed>
     */
    private function assessmentTypeCount(): array {
       return $this->documentRepo->countAssessmentSubTypes($this->property->getId());
    }

    /**
     * @return array<string, mixed>
     */
    private function documentCount(): array {
        return [
            'workOrder' => [
                'inProgress' => $this->propertyRepo->countDocumentsBoth( 
                    $this->property->getId(), 'workOrder',
                    DocumentFactory::transformStatus(DocumentStatusTypes::IN_PROGRESS)
                ),
                'submitted' =>  $this->propertyRepo->countDocumentsBoth( 
                    $this->property->getId(), 'workOrder',
                    DocumentFactory::transformStatus(DocumentStatusTypes::SUBMITTED)
                ),
               ],
            'assessment' => [
                'inProgress' =>$this->propertyRepo->countDocumentsBoth( 
                    $this->property->getId(), 'assessment',
                    DocumentFactory::transformStatus(DocumentStatusTypes::IN_PROGRESS)
                ),
                'submitted' => $this->propertyRepo->countDocumentsBoth(
                    $this->property->getId(), 'assessment',
                    DocumentFactory::transformStatus(DocumentStatusTypes::SUBMITTED)
                ),
            ],
        ];
    }
}