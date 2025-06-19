<?php 
namespace App\Transformer;

use App\Entity\Property;

class PropertyTransformer {

    public function transform(Property $property): array {
        return [
            'id'        => $property->getId(),
            'data'      => $property->getData(),
            'archived'  => $property->isArchived(),
        ];
    }

    /**
     * @param array<int,Property> $properties
     */
    public function transformPaginate(array $properties): array {
        $properties['data'] ??= [];

        foreach($properties['data'] as $property){
            $data = $this->transform($property);
            $data['countDocuments'] = $property->countDocuments;
            $results[] = $data;
        }

        $properties['data'] = $results ?? [];
        return $properties;
    }
}