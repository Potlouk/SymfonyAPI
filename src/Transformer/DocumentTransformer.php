<?php 
namespace App\Transformer;

use App\Entity\Document;
use App\Entity\Setting;

class DocumentTransformer {

    public function transform(Document $document, Setting $settings, bool $single = true): array {

        foreach ($document->getLabels() as $label)
        $labels[] = [
            'id'   => $label->getId(),
            'data' => $label->getData()
        ];

        $result = [
            'labels'        => $labels ?? [],
            'uuid'          => $document->getUuid()?->toString(),
            'info'          => $document->getInfo(),
            'property'      => [
                'id'      => $document->getProperty()?->getId(),
                'name'    => $document->getProperty()?->getName(),
                'manager' => $document->getProperty()?->getManager(),
                'logo'    => $document->getProperty()?->getLogo(),
                'industry'=> $settings->getIndustry(),
            ],
            'type'          => $document->getType(),
            'templateUuid'  => $document->getTemplate()?->getUuid()?->toString(),
            'assignedUsers' => $document->getAssignedUsers()->map(fn($user) => $user->getId())->toArray(),
            'createdAt'     => $document->getCreatedAt()?->format('Y-m-d H:i:s.u'),
            'updatedAt'     => $document->getUpdatedAt()?->format('Y-m-d H:i:s.u'),
            'sharedInfo'    => [
                'receiver'    => $document->getToken()?->getReceiver(),
                'permissions' => $document->getToken()?->getPermissions()?->getValue(),
                'expiryDate'  => $document->getToken()?->getExpiryDate()?->format('Y-m-d H:i:s'),
                'validDate'   => $document->getToken()?->getValidDate()?->format('Y-m-d H:i:s'),
                'status'      => $document->getStatus(),
            ],
            'reportReference' => [
                'uuid' => $document->getMadeFromReport()?->getUuid(),
                'name' => $document->getMadeFromReport()?->getName(),
            ]
        ];

        if ($single)
        $result['data'] = $document->getData();
    
        return $result; 
    }
    /**
     * @param array<int,Document> $documents
     */
    public function transformPagination(array $documents, Setting $settings): array {
        $documents['data'] ??= [];

        foreach($documents['data'] as $document)
            $results[] = array_merge(
                $this->transform($document, $settings, false),
                [ 'log' => $document->logs ]
            );

        $documents['data'] = $results ?? [];
        return $documents;
    }
}