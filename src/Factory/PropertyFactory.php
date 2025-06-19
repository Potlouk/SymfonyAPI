<?php

namespace App\Factory;

use App\Entity\Property;

final class PropertyFactory {
    public static function build (array $data): Property{
        return (new Property())
        ->setArchived(false)
        ->setData($data["data"]);
    }
}