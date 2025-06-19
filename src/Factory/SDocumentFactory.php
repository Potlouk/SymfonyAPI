<?php

namespace App\Factory;

use App\Entity\Document;
use App\Entity\Property;
use App\Entity\Template;


final class SDocumentFactory {

    /**
     * @param array<string, mixed> $data 
     */
    public static function build (array $data, Property $property, Template $template): Document{
        return (new Document())
        ->setData($data['data'])
        ->setInfo($data['info'])
        ->setProperty($property)
        ->setType($data['type'])
        ->setTemplate($template);
    }
}