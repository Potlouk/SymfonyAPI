<?php

namespace App\Factory;

use App\Entity\Document;
use App\Entity\Property;
use App\Entity\Template;
use App\Enum\DocumentStatusTypes;

final class DocumentFactory {

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $info
     * @param Property $property
     * @param Template|null $template
     * @return Document
     */
    public static function buildFromReport(array $data, array $info, Property $property, ?Template $template): Document {
       
        $info['name'] = $data['name'];
        $info['assessmentType'] = $data['assessmentType'];
        $info['uploadImageStatus'] = ['size'=> null, 'status'=> 'UPLOADED'];

        return (new Document())
        ->setData(self::resetCost($data['data']))
        ->setProperty($property)
        ->setInfo($info)
        ->setType($data['type'])
        ->setTemplate($template)
        ->setStatus('READY')
        ->setToken(TokenFactory::build()->setActive(false));

    }

    private static function resetCost (array $data): array {
        $pattern = '/"cost":\s*\{\s*"total":\s*[0-9]+(?:\.[0-9]+)?,\s*"landlord":\s*[0-9]+(?:\.[0-9]+)?,\s*"tenant":\s*[0-9]+(?:\.[0-9]+)?\s*\}/';
        $replacement = '"cost":{"total":0,"landlord":0,"tenant":0}';

        $subject = json_encode($data);
        if (false === $subject)
            return $data;
        
        $jsonData = preg_replace($pattern, $replacement, $subject);
        return json_decode($jsonData, true) ?? $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function build(array $data, Property $property, Template $template, DocumentStatusTypes $status): Document{
        $info = $data['info'];
        $info['templateConfig'] = $template->getInfo()['config'] ?? [];

        return (new Document())
        ->setData($template->getData())
        ->setInfo($info)
        ->setProperty($property)
        ->setType($data['type'])
        ->setTemplate($template)
        ->setStatus(self::transformStatus($status))
        ->setToken(TokenFactory::build()->setActive(false));
    }

    public static function transformStatus (DocumentStatusTypes $status): string{
        return match ($status){
            DocumentStatusTypes::IN_PROGRESS 
            => 'INPROGRESS',
            DocumentStatusTypes::SUBMITTED 
            => 'SUBMITTED',
            DocumentStatusTypes::DEFAULT
            => 'READY',
            DocumentStatusTypes::EXPIRED
            => 'EXPIRED'
        };
    }
 
}