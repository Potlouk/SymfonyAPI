<?php 
namespace App\Transformer;

use App\Entity\Report;
use App\Entity\Setting;

class ReportTransformer {

    public function transform(Report $report, Setting $settings, bool $single = true): array {

        foreach($report->getLabels() as $label)
        $labels[] = [
            'id'    => $label->getId(),
            'data'  => $label->getData()
        ];

       $result = [
            'labels'        => $labels ?? [],
            'uuid'          => $report->getUuid()?->toString(),
            'document'  => [
                'uuid' => $report->getDocument()?->getUuid(),
                'name' => 
                        $report->getDocument()?->getInfo()['name'] ?? 
                        $report->getDocument()?->getInfo()['templateName'] ?? 'Not specified',
            ],
            'info'          => $report->getInfo(),
            'property'      => [
                'id'      => $report->getProperty()?->getId(),
                'name'    => $report->getProperty()?->getName(),
                'manager' => $report->getProperty()?->getManager(),
                'logo'    => $report->getProperty()?->getLogo(),
                'industry'=> $settings->getIndustry(),
            ],
            'type'          => $report->getType(),
            'token'         => $report->getToken()?->getValue(),
            'permissions'   => $report->getToken()?->getPermissions()?->getValue(),
            'assignedUsers' => $report->getAssignedUsers()->map(fn($user) => $user->getId())->toArray(),
            'createdAt'     => $report->getCreatedAt()?->format('Y-m-d H:i:s.u'),
            'updatedAt'     => $report->getUpdatedAt()?->format('Y-m-d H:i:s.u'),
        ];

        if ($single)
            $result['data'] = $report->getData();
        
       return $result; 
    }

    /**
     * @param array<int,Report> $reports
     */
    public function transformPagination(array $reports,Setting $settings): array {
        $reports['data'] ??= [];

        foreach($reports['data'] as $report){
            $results[] = array_merge(
                $this->transform($report, $settings, false),
                [ 'log' => $report->logs ]
            );
        }
        $reports['data'] = $results ?? [];
        return $reports;
    }
}