<?php

namespace App\Factory;

use App\Entity\Document;
use App\Entity\Report;
use App\Entity\Setting;

final class ReportFactory {

    /** build a report from passed shared document */
    public static function buildFromDocument(Document $document, Setting $settings): Report {
        return (new Report())
        ->setInfo( 
            self::createInfo($document, $settings)
        )->setData(
            self::createData($document)
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return Report
     */
    public static function build(array $data): Report {
        return (new Report())
        ->setInfo( 
            $data['info']
        )->setData(
            $data['data']
        )->setType(
            $data['type']
        );
    }

    /**
     * @return array<string, mixed> $data
     */
    private static function createData(Document $document): array {
        $images = [];
        $areas = $document->getData()['areas'] ?? [];

        foreach ($areas as $area)
            foreach ($area['items'] as $item)
                foreach ($item['files'] as $file) {
                    $galleryFile = $file;
                    $galleryFile['areaName'] = $area['areaName'];
                    $galleryFile['itemName'] = $item['itemName'];
                    $images[] = $galleryFile;
                }

        return ['images' => $images, 'coverImage' => null];
    }

      /**
     * @return array<string, mixed> $data
     */
    private static function createInfo(Document $document, Setting $settings): array {
        $propertyData = $document->getProperty()?->getData();
        $companySettings = $settings->getData()["companySettings"] ?? [];

        return [
            "title"             => '',
            "showLogo"          => true,
            "isEstimate"        => false,
            "allowInfo"         => true,
            "date"              => date('Y-m-d'),
            "type"              => 'assessment',
            "propertyManager"   => [
                'name'          => $propertyData['manager'] ?? $companySettings["companyName"] ?? 'Not specified',
                'logo'          => $propertyData['logo'] ?? $companySettings["companyLogo"] ?? 'Not specified',
                'industry'      => $companySettings["industry"] ?? 'Not specified',
            ],
        ];
    }
}