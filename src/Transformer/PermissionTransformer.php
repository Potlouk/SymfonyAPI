<?php 
namespace App\Transformer;

class PermissionTransformer {

    public function transform(string $permission): array {
        return [
            'name' => $permission,
        ];
    }
}