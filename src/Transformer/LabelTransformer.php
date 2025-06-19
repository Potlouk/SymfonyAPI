<?php 
namespace App\Transformer;

use App\Entity\Label;

class LabelTransformer {

    public function transform(Label $label): array {
        return [
            'id'        => $label->getId(),
            'data'      => $label->getData(),
            'createdAt' => $label->getCreatedAt()?->format('Y-m-d H:i:s.u'),
            'updatedAt' => $label->getUpdatedAt()?->format('Y-m-d H:i:s.u'),
        ];
    }

    /**
     * @param array<int,Label> $labels
     */
    public function transformAll(array $labels): array {
        foreach($labels as $label)
        $results[] = $this->transform($label);

        return $results ?? [];
    }
}