<?php

namespace App\Transformer;

use App\Model\BuildInformation;

class BuildInformationTransformer
{
    public function transform(BuildInformation $build): array {
        return [
            'type'    => $build->getType(),
            'version' => $build->getVersion(),
            'build'   => $build->getBuild(),
        ];
    }
}