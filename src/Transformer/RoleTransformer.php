<?php 
namespace App\Transformer;

use App\Entity\Role;

class RoleTransformer {

    public function transform(Role $role): array {
        return [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'treeIds'=> $role->getTreeIds(),
        ];
    }

    /**
     * @param array<int,Role> $roles
     */
    public function transformAll(array $roles): array {
        foreach($roles as $role)
        $results[] = $this->transform($role);

        return $results ?? [];
    }
}