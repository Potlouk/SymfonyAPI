<?php 
namespace App\Transformer;

use App\Entity\Setting;

class SettingsTransformer {

    public function transform(Setting $settings): array {
        return $settings->getData();
    }

}